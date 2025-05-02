#!/bin/bash

# Phase 1: Verify installations
echo "=== VERIFICATION ==="
echo "1. PHP: $(php -v | head -n 1)"
echo "2. Composer: $(composer -V)"
echo "3. npm: v$(npm -v)"

# Phase 2: Start MySQL
echo "=== STARTING MYSQL ==="
sudo service mysql start

# Phase 3: Database Setup
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

# Create database and user
echo "=== CREATING DATABASE ==="
sudo mysql -uroot -e "CREATE DATABASE IF NOT EXISTS beefree;"
sudo mysql -uroot -e "SHOW DATABASES;"

echo "=== CREATING USER ==="
sudo mysql -uroot <<EOF
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
FLUSH PRIVILEGES;
EOF

# Configure MySQL root access
echo "=== CONFIGURING MYSQL ACCESS ==="
sudo mysql -uroot <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EOF

# Update Laravel .env
echo "=== UPDATING LARAVEL CONFIG ==="
if [ -f ".env" ]; then
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=beefree/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=password/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
fi

# Install dependencies
echo "=== INSTALLING DEPENDENCIES ==="
composer install
npm install

# Generate Laravel app key if missing
if [ -f "artisan" ] && [ -z "$(grep '^APP_KEY=base64:' .env)" ]; then
    echo "=== GENERATING LARAVEL APP KEY ==="
    php artisan key:generate
fi

# Phase 4: Seed Database
echo "=== SEEDING DATABASE ==="
php artisan migrate --force
php artisan db:seed --force
echo "Database seeded!"

echo ""
echo "🎉 SETUP COMPLETE!"
