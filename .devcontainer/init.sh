#!/bin/bash
set -e # Exit immediately if any command fails

# Step 1: Create .env if missing
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

# Step 2: Install PHP dependencies (with retry logic)
for i in {1..3}; do
    if composer install; then
        break
    else
        echo "Composer install failed (attempt $i/3), retrying..."
        if [ $i -eq 3 ]; then
            echo "Composer install failed after 3 attempts"
            exit 1
        fi
        sleep 5
    fi
done

# Step 3: Install Node dependencies
npm install

# Step 4: Run the main setup script
bash .devcontainer/setup.sh
