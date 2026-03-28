#!/bin/bash
# Fallback to 80 if PORT isn't provided
PORT=${PORT:-80}

# Update Apache configuration files with the dynamic PORT
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Force Laravel to securely log errors directly to Render's terminal stream!
export LOG_CHANNEL=stderr
export APP_DEBUG=true

# Explicitly guarantee storage and database folders are writable by Apache FIRST
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# NOW execute migrations as www-data to ensure the SQLite database doesn't get locked by root!
su -s /bin/bash -c "php artisan migrate --force" www-data

# Start Apache in foreground
docker-php-entrypoint apache2-foreground
