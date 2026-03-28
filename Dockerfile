FROM php:8.2-apache

# Install dependencies required by Laravel and SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql gd

# Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Update Apache DocumentRoot to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the entire Laravel application
COPY . .

# Clear old cached files so Composer works flawlessly
RUN rm -rf vendor composer.lock

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Set secure permissions for Apache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Bind Apache to Render's required PORT environment variable
RUN echo "Listen \${PORT:-80}" >> /etc/apache2/ports.conf
RUN sed -ri -e 's!:80!:${PORT:-80}!g' /etc/apache2/sites-available/*.conf

# Ensure migrations execute during container startup (fallback for SQLite caching)
RUN touch /var/www/html/database/database.sqlite
RUN chown www-data:www-data /var/www/html/database/database.sqlite
RUN chmod 777 /var/www/html/database/database.sqlite

EXPOSE 80
