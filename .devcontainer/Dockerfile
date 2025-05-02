FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

# Install only essential packages
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive && \
    apt-get install -y \
    nodejs \
    npm \
    php-cli \
    php-mysql 

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configure MySQL
RUN usermod -d /var/lib/mysql/ mysql && \
    mkdir -p /var/run/mysqld && \
    chown -R mysql:mysql /var/run/mysqld

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
