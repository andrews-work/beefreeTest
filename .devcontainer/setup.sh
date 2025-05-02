# setup.sh

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

# Run the install script
bash .devcontainer/install.sh

# Start the development servers
composer run dev
