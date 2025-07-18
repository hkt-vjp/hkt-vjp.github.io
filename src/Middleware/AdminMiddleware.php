<?php

namespace NetflixLoginService\Middleware;

use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Router;

class AdminMiddleware
{
    public function handle()
    {
        $session = new Session();
        
        if (!$session->isLoggedIn()) {
            Router::redirect('/login');
            return false;
        }
        
        $user = $session->getUser();
        
        // Check if user has admin role
        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            http_response_code(403);
            echo "<h1>403 - Access Forbidden</h1>";
            echo "<p>You don't have permission to access this area.</p>";
            return false;
        }
        
        return true;
    }
}