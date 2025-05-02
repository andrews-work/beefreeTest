FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

# 1. Install only essential packages
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive && \
    apt-get install -y \
    nodejs \
    npm \
    php-cli \
    php-mysql \
    php-dom \
    php-xml \
    mysql-server && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Basic MySQL config
RUN usermod -d /var/lib/mysql/ mysql
