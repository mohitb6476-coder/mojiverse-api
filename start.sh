#!/bin/bash
# Fallback to 80 if PORT isn't provided (e.g. local Docker testing)
PORT=\${PORT:-80}

# Update Apache configuration files with the dynamic PORT
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Execute migrations just in case (optional, safe for SQLite)
php artisan migrate --force

# Start Apache in foreground
docker-php-entrypoint apache2-foreground
