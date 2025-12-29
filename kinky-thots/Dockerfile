# Kinky-Thots Docker Setup
#
# This project uses Docker Compose with multiple services.
# Use docker-compose.yml instead of building this file directly.
#
# Quick Start:
#   1. Copy .env.docker to .env and configure values
#   2. Run: docker-compose up --build -d
#
# Services:
#   - web: Apache/PHP (port 80)
#   - backend: Node.js chat/API (port 3001)
#   - rtmp: nginx-rtmp streaming (port 1935)
#   - db: MariaDB database (port 3306)
#
# For individual container builds, see docker/ directory.

# This is a convenience wrapper that builds the web container
FROM php:8.4-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        curl \
        mysqli \
        pdo_mysql \
        gd \
        fileinfo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers expires

WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
