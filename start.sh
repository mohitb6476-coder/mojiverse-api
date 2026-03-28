#!/bin/bash
# Fallback to 80 if PORT isn't provided
PORT=${PORT:-80}

# Update Apache configuration files with the dynamic PORT
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Execute migrations as www-data to ensure the SQLite database doesn't get locked by root!
su -s /bin/bash -c "php artisan migrate --force" www-data

# Explicitly guarantee storage and database folders are writable by Apache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Start Apache in foreground
docker-php-entrypoint apache2-foreground
