#!/bin/bash
set -e # Exit immediately if any command fails

echo "=== CONFIGURING ENVIRONMENT ==="
if [ ! -f ".env" ]; then
    cp .env.example .env
    # Force MySQL configuration
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=beefree/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=beefree/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=password/" .env
fi

echo "=== INSTALLING DEPENDENCIES ==="
composer install --no-interaction
npm install --no-audit

echo "=== SETTING UP MYSQL ==="
sudo service mysql start

# Wait for MySQL to be ready
for i in {1..10}; do
    if sudo mysql -uroot -e "SHOW DATABASES;" &>/dev/null; then
        break
    else
        echo "Waiting for MySQL (attempt $i/10)..."
        sleep 2
    fi
done

# Create database and user
sudo mysql -uroot <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS beefree;
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo "=== SETTING UP LARAVEL ==="
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force

echo "✅ SETUP COMPLETE - Run 'Start Development Servers' task to begin"
