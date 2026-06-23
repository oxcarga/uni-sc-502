#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until mysqladmin ping -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent; do
  echo 'waiting for mysql...'
  sleep 1
done

echo "MySQL is ready!"

# Set proper permissions on the web root
chown -R www-data:www-data /var/www/html

# Create frontend public directory if it doesn't exist
mkdir -p /var/www/html/public

echo "Container initialization complete!"

exec "$@"
