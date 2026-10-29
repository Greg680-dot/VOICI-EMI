#!/bin/sh
set -e

# Default PORT for Render if not set
export PORT="${PORT:-10000}"
echo "=> Configuring Nginx to listen on port $PORT..."
sed -i "s/\${PORT}/$PORT/g" /etc/nginx/http.d/default.conf

# SQLite auto-provisioning if no external database is configured
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    echo "=> Setting up SQLite database..."
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
fi

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "=> Generating application encryption key..."
    php artisan key:generate --force
fi

# Ensure storage and cache directory permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create public storage symlink
php artisan storage:link --force || true

# Run database migrations
echo "=> Executing database migrations..."
php artisan migrate --force

# Seed database with initial actors, courses and articles
echo "=> Seeding database records..."
php artisan db:seed --force || true

# Cache Laravel configurations for maximum performance
echo "=> Caching routes and config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Launch Supervisord to run PHP-FPM and Nginx
echo "=> Launching VoiCI-EMI platform on port $PORT..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
