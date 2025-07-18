<?php

namespace NetflixLoginService\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use NetflixLoginService\Config\Database;
use NetflixLoginService\Utils\Logger;
use NetflixLoginService\Core\Security;
use Exception;

class GmailService
{
    private $googleClient;
    private $logger;
    private $db;
    private $security;
    
    public function __construct()
    {
        $this->logger = new Logger();
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
        $this->initializeGoogleClient();
    }
    
    private function initializeGoogleClient()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $this->googleClient->addScope(Gmail::GMAIL_READONLY);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
    }
    
    public function getAuthUrl($state = null)
    {
        if ($state) {
            $this->googleClient->setState($state);
        }
        
        return $this->googleClient->createAuthUrl();
    }
    
    public function handleCallback($code, $state = null)
    {
        try {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception("OAuth error: " . $token['error_description']);
            }
            
            $this->googleClient->setAccessToken($token);
            
            // Get user email
            $gmail = new Gmail($this->googleClient);
            $profile = $gmail->users->getProfile('me');
            $email = $profile->getEmailAddress();
            
            return [
                'success' => true,
                'email' => $email,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => isset($token['expires_in']) ? time() + $token['expires_in'] : null
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Gmail OAuth callback failed", [
                'error' => $e->getMessage(),
                'code' => $code
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function saveTokens($emailId, $accessToken, $refreshToken, $expiresAt)
    {
        try {
            // Encrypt tokens before storing
            $encryptedAccessToken = $this->security->encryptData($accessToken);
            $encryptedRefreshToken = $refreshToken ? $this->security->encryptData($refreshToken) : null;
            
            $stmt = $this->db->prepare("
                UPDATE netflix_emails 
                SET gmail_access_token = ?, 
                    gmail_refresh_token = ?, 
                    token_expires_at = FROM_UNIXTIME(?),
                    status = 'active',
                    last_sync = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $encryptedAccessToken,
                $encryptedRefreshToken,
                $expiresAt,
                $emailId
            ]);
            
        } catch (Exception $e) {
            $this->logger->error("Failed to save Gmail tokens", [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function refreshAccessToken($emailId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT gmail_refresh_token, email 
                FROM netflix_emails 
                WHERE id = ? AND gmail_refresh_token IS NOT NULL
            ");
            $stmt->execute([$emailId]);
            $emailData = $stmt->fetch();
            
            if (!$emailData) {
                throw new Exception("No refresh token found for email ID: {$emailId}");
            }
            
            $refreshToken = $this->security->decryptData($emailData['gmail_refresh_token']);
            
            $this->googleClient->setAccessToken([
                'refresh_token' => $refreshToken
            ]);
            
            $newToken = $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
            
            if (isset($newToken['error'])) {
                throw new Exception("Token refresh failed: " . $newToken['error_description']);
            }
            
            // Save new access token
            $expiresAt = isset($newToken['expires_in']) ? time() + $newToken['expires_in'] : null;
            $this->saveTokens($emailId, $newToken['access_token'], $refreshToken, $expiresAt);
            
            $this->logger->info("Access token refreshed successfully", [
                'email_id' => $emailId,
                'email' => $emailData['email']
            ]);
            
            return [
                'success' => true,
                'access_token' => $newToken['access_token'],
                'expires_at' => $expiresAt
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to refresh access token", [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            
            // Mark email as error status
            $this->updateEmailStatus($emailId, 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getLatestNetflixCode($emailId)
    {
        try {
            // Get email data and tokens
            $stmt = $this->db->prepare("
                SELECT email, gmail_access_token, gmail_refresh_token, token_expires_at 
                FROM netflix_emails 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$emailId]);
            $emailData = $stmt->fetch();
            
            if (!$emailData) {
                throw new Exception("Email not found or inactive");
            }
            
            $accessToken = $this->security->decryptData($emailData['gmail_access_token']);
            
            // Check if token needs refresh
            if ($emailData['token_expires_at'] && strtotime($emailData['token_expires_at']) <= time() + 300) {
                $refreshResult = $this->refreshAccessToken($emailId);
                if (!$refreshResult['success']) {
                    throw new Exception("Failed to refresh token: " . $refreshResult['error']);
                }
                $accessToken = $refreshResult['access_token'];
            }
            
            // Set up Gmail client
            $this->googleClient->setAccessToken(['access_token' => $accessToken]);
            $gmail = new Gmail($this->googleClient);
            
            // Search for Netflix emails
            $query = 'from:info@account.netflix.com OR from:netflix.com "sign in" OR "verification code" OR "login code"';
            $messages = $gmail->users_messages->listUsersMessages('me', [
                'q' => $query,
                'maxResults' => 10
            ]);
            
            if (empty($messages->getMessages())) {
                return [
                    'success' => false,
                    'error' => 'No Netflix login emails found'
                ];
            }
            
            // Get the latest message
            $latestMessage = $messages->getMessages()[0];
            $messageDetails = $gmail->users_messages->get('me', $latestMessage->getId());
            
            // Extract login code and link
            $result = $this->extractNetflixCode($messageDetails);
            
            // Update last sync time
            $this->updateLastSync($emailId);
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get Netflix code", [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function extractNetflixCode($message)
    {
        $headers = $message->getPayload()->getHeaders();
        $subject = '';
        $date = '';
        
        foreach ($headers as $header) {
            if ($header->getName() === 'Subject') {
                $subject = $header->getValue();
            }
            if ($header->getName() === 'Date') {
                $date = $header->getValue();
            }
        }
        
        // Get message body
        $body = $this->getMessageBody($message->getPayload());
        
        // Extract verification code (usually 6-8 digits)
        $codePattern = '/\b(\d{6,8})\b/';
        $loginCode = null;
        if (preg_match($codePattern, $body, $matches)) {
            $loginCode = $matches[1];
        }
        
        // Extract login links
        $linkPattern = '/https:\/\/[^\s]*netflix[^\s]*verify[^\s]*/i';
        $loginLink = null;
        if (preg_match($linkPattern, $body, $matches)) {
            $loginLink = $matches[0];
        }
        
        // Alternative link pattern
        if (!$loginLink) {
            $linkPattern = '/https:\/\/[^\s]*account\.netflix\.com[^\s]*/i';
            if (preg_match($linkPattern, $body, $matches)) {
                $loginLink = $matches[0];
            }
        }
        
        return [
            'success' => true,
            'login_code' => $loginCode,
            'login_link' => $loginLink,
            'email_subject' => $subject,
            'email_date' => $date,
            'email_body' => $body // For debugging
        ];
    }
    
    private function getMessageBody($payload)
    {
        $body = '';
        
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain' || $part->getMimeType() === 'text/html') {
                    $data = $part->getBody()->getData();
                    $body .= base64_decode(strtr($data, '-_', '+/'));
                } elseif ($part->getParts()) {
                    $body .= $this->getMessageBody($part);
                }
            }
        } else {
            $data = $payload->getBody()->getData();
            $body = base64_decode(strtr($data, '-_', '+/'));
        }
        
        return $body;
    }
    
    private function updateEmailStatus($emailId, $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE netflix_emails SET status = ? WHERE id = ?");
            $stmt->execute([$status, $emailId]);
        } catch (Exception $e) {
            $this->logger->error("Failed to update email status", [
                'email_id' => $emailId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function updateLastSync($emailId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE netflix_emails SET last_sync = NOW() WHERE id = ?");
            $stmt->execute([$emailId]);
        } catch (Exception $e) {
            $this->logger->error("Failed to update last sync time", [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function disconnectEmail($emailId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE netflix_emails 
                SET gmail_access_token = NULL, 
                    gmail_refresh_token = NULL, 
                    token_expires_at = NULL,
                    status = 'inactive'
                WHERE id = ?
            ");
            
            return $stmt->execute([$emailId]);
        } catch (Exception $e) {
            $this->logger->error("Failed to disconnect Gmail", [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}