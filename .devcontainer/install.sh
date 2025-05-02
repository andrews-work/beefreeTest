#!/bin/bash
set -e # Exit immediately if any command fails

echo "=== CREATING .env ==="
[ -f ".env" ] || cp .env.example .env

echo "=== INSTALLING COMPOSER DEPENDENCIES ==="
for i in {1..3}; do
    if composer install; then
        break
    elif [ $i -eq 3 ]; then
        echo "Composer install failed after 3 attempts"
        exit 1
    else
        echo "Retrying composer install..."
        sleep 5
    fi
done

echo "=== INSTALLING NODE DEPENDENCIES ==="
npm install

echo "=== CONFIGURING MYSQL ==="
sudo service mysql start

echo "=== TESTING MYSQL CONNECTION ==="
for i in {1..10}; do
    if sudo mysql -uroot -e "SHOW DATABASES;" &>/dev/null; then
        break
    elif [ $i -eq 10 ]; then
        echo "MySQL connection failed"
        exit 1
    else
        sleep 2
    fi
done

echo "=== SETTING UP DATABASE ==="
sudo mysql -uroot <<EOF
CREATE DATABASE IF NOT EXISTS beefree;
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EOF

echo "=== CONFIGURING LARAVEL ==="
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=beefree/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=password/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env

echo "=== GENERATING APP KEY ==="
php artisan key:generate

echo "=== RUNNING MIGRATIONS ==="
php artisan migrate --force
php artisan db:seed --force

echo "🎉 SETUP COMPLETE! Run 'composer run dev' to start servers."
