FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

# Install system dependencies
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive && \
    apt-get install -y \
    nodejs \
    npm \
    php-cli \
    php-mysql \
    php-xml \
    php-zip \
    php-mbstring \
    php-curl \
    php-tokenizer \
    php-json \
    php-bcmath \
    mysql-server

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure MySQL
RUN usermod -d /var/lib/mysql/ mysql
