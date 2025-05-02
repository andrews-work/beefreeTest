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

echo "=== STEP 5: Prepare Database ==="
# Uncomment and set MySQL settings (handles both commented and uncommented cases)
sed -i "s/^#\?DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/^#\?DB_HOST=.*/DB_HOST=127.0.0.1/" .env
sed -i "s/^#\?DB_PORT=.*/DB_PORT=3306/" .env
sed -i "s/^#\?DB_DATABASE=.*/DB_DATABASE=beefree/" .env
sed -i "s/^#\?DB_USERNAME=.*/DB_USERNAME=beefree/" .env
sed -i "s/^#\?DB_PASSWORD=.*/DB_PASSWORD=password/" .env

# Verify changes
echo "=== Updated .env ==="
grep -E "^DB_(CONNECTION|HOST|PORT|DATABASE|USERNAME|PASSWORD)" .env

echo "✅ STEPS 1-5 COMPLETED SUCCESSFULLY"
