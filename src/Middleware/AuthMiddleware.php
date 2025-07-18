<?php

namespace NetflixLoginService\Middleware;

use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Router;

class AuthMiddleware
{
    public function handle()
    {
        $session = new Session();
        
        if (!$session->isLoggedIn()) {
            // Store the intended URL for redirect after login
            $session->set('intended_url', $_SERVER['REQUEST_URI']);
            
            // Redirect to login page
            Router::redirect('/login');
            return false;
        }
        
        return true;
    }
}