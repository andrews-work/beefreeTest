#!/bin/bash
set -e # Exit immediately if any command fails

echo "=== STARTING MYSQL ==="
sudo service mysql start

echo "=== TESTING MYSQL CONNECTION ==="
for i in {1..10}; do
    if sudo mysql -uroot -e "SHOW DATABASES;" &>/dev/null; then
        echo "MySQL connection successful!"
        break
    else
        echo "Waiting for MySQL connection (attempt $i/10)..."
        sleep 2
    fi
done

echo "=== CREATING DATABASE ==="
sudo mysql -uroot -e "CREATE DATABASE IF NOT EXISTS beefree;"
sudo mysql -uroot -e "SHOW DATABASES;"

echo "=== CREATING USER ==="
sudo mysql -uroot <<EOF
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "=== CONFIGURING MYSQL ACCESS ==="
sudo mysql -uroot <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EOF

echo "=== UPDATING LARAVEL CONFIG ==="
if [ -f ".env" ]; then
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=beefree/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=password/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
fi

echo "=== GENERATING LARAVEL APP KEY ==="
php artisan key:generate

echo "=== SEEDING DATABASE ==="
php artisan migrate --force
php artisan db:seed --force

