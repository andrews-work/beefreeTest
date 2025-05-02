#!/bin/bash
set -e

# 1. Fix permissions FIRST
sudo chown -R vscode:vscode /workspaces/beefreeTest
sudo chmod -R 755 /workspaces/beefreeTest

# 2. Environment setup
if [ ! -f ".env" ]; then
    cp .env.example .env
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=beefree/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=beefree/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=password/" .env
fi

# 3. Start MySQL
sudo service mysql start

# 4. Wait for MySQL
for i in {1..10}; do
  if sudo mysql -uroot -e "SHOW DATABASES;" &>/dev/null; then
    break
  else
    echo "Waiting for MySQL (attempt $i/10)..."
    sleep 2
  fi
done

# 5. Create database
sudo mysql -uroot <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS beefree;
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# 6. Install dependencies
composer install --no-interaction --no-progress
npm install --no-audit

# 7. Laravel setup
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force

echo "✅ Setup complete! Run 'composer run dev' when ready."
