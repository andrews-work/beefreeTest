# Use the base image
FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

# Update the package list and install dependencies
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive

# Install Node.js, npm, PHP, MySQL and related packages
RUN apt-get install -y \
    nodejs \
    npm \
    php-cli \
    php-mysql \
    php-xml \
    mysql-server

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Clean up apt cache to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure MySQL to run as root in development
RUN usermod -d /var/lib/mysql/ mysql
