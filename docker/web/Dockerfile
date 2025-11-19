FROM php:8.1-apache

# Allow mapping the Apache/PHP user and group to the host IDs so bind-mounted
# files (like config/soft_skills) remain writable from inside the container.
ARG WWWGROUP=33
ARG WWWUSER=33
RUN groupmod -o -g ${WWWGROUP} www-data \
    && usermod -o -u ${WWWUSER} www-data

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Enable Apache modules
RUN a2enmod rewrite ssl headers expires deflate

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# Configure PHP
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Create health check endpoint
COPY health-check.php /var/www/html/health-check.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/health-check.php || exit 1

# Start Apache
CMD ["apache2-foreground"]
