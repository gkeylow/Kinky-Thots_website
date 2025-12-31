FROM php:8.4-apache

# Install PHP extensions and certbot for SSL
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    certbot \
    python3-certbot-apache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        curl \
        mysqli \
        pdo_mysql \
        gd \
        fileinfo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules (including proxy for API and SSL for future HTTPS)
RUN a2enmod rewrite headers expires proxy proxy_http proxy_wstunnel ssl

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# To use certbot for SSL:
# docker exec -it kinky-web certbot --apache -d kinky-thots.com -d www.kinky-thots.com

CMD ["apache2-foreground"]
