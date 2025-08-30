#!/bin/ash -e
cd /app

extract_domain() {
    echo "$APP_URL" | sed 's~https*://~~g' | sed 's~/.*~~g'
}

if [ -z "$APP_URL" ]; then
    echo "ERROR: APP_URL is required"
    exit 1
fi

if [ -z "$DB_HOST" ]; then
    echo "ERROR: DB_HOST is required"
    exit 1
fi

mkdir -p /var/log/supervisord/ /var/log/nginx/ /var/log/php7/
chmod 755  /var/log/supervisord/ /var/log/nginx/ /var/log/php7/
chown nginx:nginx /var/log/nginx/

if [ ! -f /app/.env ]; then
    echo "Copying .env.example to .env"
    cp /app/.env.example /app/.env
fi

DOMAIN=$(extract_domain)
echo "Detected domain: $DOMAIN"

echo "Configuring nginx..."
if [ -f /etc/nginx/http.d/clientxcms.conf ]; then
    echo "Using existing nginx config"
    if [ "$LETSENCRYPT_EMAIL" ]; then
        echo "Checking for cert renewal"
        if ! certbot certonly -d "$DOMAIN" --standalone -m "$LETSENCRYPT_EMAIL" --agree-tos -n --keep-until-expiring; then
            echo "WARNING: Certificate generation/renewal failed"
        fi
    else
        echo "No letsencrypt email set"
    fi
else
    echo "Setting up nginx configuration"
    rm -f /etc/nginx/http.d/default.conf

    if [ -z "$LETSENCRYPT_EMAIL" ]; then
        echo "Using HTTP configuration"
        cp .github/docker/default.conf /etc/nginx/http.d/clientxcms.conf
    else
        echo "Using HTTPS configuration"
        cp .github/docker/default_ssl.conf /etc/nginx/http.d/clientxcms.conf

        echo "Generating SSL certificates"
        if ! certbot certonly -d "$DOMAIN" --standalone -m "$LETSENCRYPT_EMAIL" --agree-tos -n; then
            echo "ERROR: Certificate generation failed"
            echo "Falling back to HTTP configuration"
            cp .github/docker/default.conf /etc/nginx/http.d/clientxcms.conf
        fi
    fi

    echo "Updating domain in nginx config"
    sed -i "s|<domain>|$DOMAIN|g" /etc/nginx/http.d/clientxcms.conf
fi

DB_PORT=${DB_PORT:-3306}
echo "Using DB_PORT: $DB_PORT"
echo "Waiting for database connection..."
TIMEOUT=60
COUNTER=0

while ! nc -z -v -w5 "$DB_HOST" "$DB_PORT"; do
    echo "Database not ready, waiting... ($COUNTER/$TIMEOUT)"
    sleep 2
    COUNTER=$((COUNTER + 1))

    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "ERROR: Database connection timeout after ${TIMEOUT} attempts"
        exit 1
    fi
done

echo "Database connection established"
echo "Running Laravel setup..."
php artisan migrate --seed --force || {
    echo "ERROR: Migration failed"
    exit 1
}

chown nginx:nginx /app/.env
php artisan key:generate || {
    echo "ERROR: Key generation failed"
    exit 1
}
php artisan translations:import $DEFAULT_LOCALE || {
    echo "ERROR: Key generation failed"
    exit 1
}

echo "Starting cron jobs..."
crond -L /var/log/crond -l 5

echo "Starting supervisord..."
exec "$@"
