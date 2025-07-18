#!/bin/bash

# Netflix Login Code Service - Auto Project Creator
# Run this script to automatically create the project structure

echo "🚀 Creating Netflix Login Code Service project..."

# Create main project directory
PROJECT_NAME="netflix-login-service"
mkdir -p $PROJECT_NAME
cd $PROJECT_NAME

echo "📁 Creating directory structure..."

# Create directory structure
mkdir -p database
mkdir -p public/assets/css
mkdir -p public/assets/js
mkdir -p public/assets/images
mkdir -p src/Config
mkdir -p src/Core
mkdir -p src/Controllers/Auth
mkdir -p src/Controllers/Admin
mkdir -p src/Controllers/Api
mkdir -p src/Middleware
mkdir -p src/Services
mkdir -p src/Utils
mkdir -p src/Views/layouts
mkdir -p src/Views/auth
mkdir -p src/Views/admin
mkdir -p src/Views/public
mkdir -p src/Views/errors
mkdir -p storage/logs
mkdir -p storage/cache
mkdir -p storage/backups
mkdir -p storage/uploads

echo "✅ Directory structure created!"

echo "📄 Creating essential files..."

# Create .gitignore
cat > .gitignore << 'EOF'
# Dependencies
/vendor/
composer.lock

# Environment
.env
.env.local
.env.production

# Logs
storage/logs/*.log
storage/logs/*.txt

# Cache
storage/cache/*
!storage/cache/.gitkeep

# Uploads
storage/uploads/*
!storage/uploads/.gitkeep

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Backup
*.sql
*.backup
EOF

# Create storage .gitkeep files
touch storage/logs/.gitkeep
touch storage/cache/.gitkeep
touch storage/backups/.gitkeep
touch storage/uploads/.gitkeep

# Create composer.json
cat > composer.json << 'EOF'
{
    "name": "netflix-login-code-service/admin-panel",
    "description": "Netflix Login Code Service - Admin Panel for managing Netflix login codes with Gmail API integration",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "google/apiclient": "^2.12",
        "vlucas/phpdotenv": "^5.4",
        "firebase/php-jwt": "^6.3",
        "phpmailer/phpmailer": "^6.6",
        "ramsey/uuid": "^4.2",
        "monolog/monolog": "^2.8"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    },
    "autoload": {
        "psr-4": {
            "NetflixLoginService\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"copy('.env.example', '.env');\""
        ]
    }
}
EOF

echo "✅ Essential files created!"

echo "🔧 To complete the setup:"
echo "1. Copy all PHP files from the conversation"
echo "2. Run: composer install"
echo "3. Copy .env.example to .env and configure"
echo "4. Run the installer: php -S localhost:8000 -t public"
echo "5. Visit: http://localhost:8000/install.php"

echo ""
echo "📂 Project created in: $(pwd)"
echo "🎉 Netflix Login Code Service project structure is ready!"
EOF