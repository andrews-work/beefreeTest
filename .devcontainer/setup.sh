#!/bin/bash
set -e

echo "=== STEP 1: Verify dependencies ==="
echo "Node: $(node -v)"
echo "npm: $(npm -v)"
echo "PHP: $(php -v | head -n 1)"
echo "Composer: $(composer -V)"
echo "MySQL: $(mysql --version)"

echo "=== STEP 2: Configure environment ==="
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi
echo "DB_CONNECTION: $(grep DB_CONNECTION .env)"

echo "=== STEP 3: Start MySQL ==="
sudo service mysql start
echo "MySQL status: $(sudo service mysql status | head -n 1)"

echo "=== STEP 4: Create MySQL user ==="
sudo mysql -uroot <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS beefree;
CREATE USER IF NOT EXISTS 'beefree'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beefree.* TO 'beefree'@'localhost';
SHOW GRANTS FOR 'beefree'@'localhost';
MYSQL_SCRIPT

echo "✅ STEPS 1-4 COMPLETED SUCCESSFULLY"
