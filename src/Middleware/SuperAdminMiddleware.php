<?php

namespace NetflixLoginService\Middleware;

use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Router;

class SuperAdminMiddleware
{
    public function handle()
    {
        $session = new Session();
        
        if (!$session->isLoggedIn()) {
            Router::redirect('/login');
            return false;
        }
        
        if (!$session->isSuperAdmin()) {
            http_response_code(403);
            echo "<h1>403 - Access Forbidden</h1>";
            echo "<p>Only super administrators can access this area.</p>";
            return false;
        }
        
        return true;
    }
}