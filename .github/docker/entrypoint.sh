#!/bin/sh
set -e

cd /app

# -- Validate required environment variables --------------------------------
if [ -z "$APP_URL" ]; then
    echo "ERROR: APP_URL is required"
    exit 1
fi

if [ -z "$DB_HOST" ]; then
    echo "ERROR: DB_HOST is required"
    exit 1
fi

# -- Wait for database -------------------------------------------------------
DB_PORT=${DB_PORT:-3306}
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
COUNTER=0
MAX_ATTEMPTS=60

while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ "$COUNTER" -ge "$MAX_ATTEMPTS" ]; then
        echo "ERROR: Database connection timeout after ${MAX_ATTEMPTS} attempts"
        exit 1
    fi
    echo "  Database not ready ($COUNTER/$MAX_ATTEMPTS)"
    sleep 2
done
echo "Database connection established"

# -- Wait for Redis (if configured) ------------------------------------------
REDIS_HOST=${REDIS_HOST:-}
REDIS_PORT=${REDIS_PORT:-6379}

if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for Redis at ${REDIS_HOST}:${REDIS_PORT}..."
    COUNTER=0
    while ! nc -z "$REDIS_HOST" "$REDIS_PORT" 2>/dev/null; do
        COUNTER=$((COUNTER + 1))
        if [ "$COUNTER" -ge "$MAX_ATTEMPTS" ]; then
            echo "ERROR: Redis connection timeout after ${MAX_ATTEMPTS} attempts"
            exit 1
        fi
        echo "  Redis not ready ($COUNTER/$MAX_ATTEMPTS)"
        sleep 2
    done
    echo "Redis connection established"
fi

# -- Prepare storage directories (MUST run before any artisan command) --------
echo "Preparing storage directories..."
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache modules addons
chmod -R 775 storage bootstrap/cache modules addons

# -- Create .env in storage (writable PVC) and symlink from /app/.env ----------
if [ ! -f /app/storage/.env ]; then
    echo "Creating .env from .env.example"
    cp /app/.env.example /app/storage/.env
fi
ln -sf /app/storage/.env /app/.env

# -- Inject K8s environment variables into .env -------------------------------
# Write directly to the PVC file to avoid sed -i breaking the symlink
ENV_FILE="/app/storage/.env"

inject_env() {
    VAR_NAME="$1"
    VAR_VALUE="$2"
    if [ -n "$VAR_VALUE" ]; then
        if grep -q "^${VAR_NAME}=" "$ENV_FILE"; then
            sed -i "s|^${VAR_NAME}=.*|${VAR_NAME}=${VAR_VALUE}|" "$ENV_FILE"
        else
            echo "${VAR_NAME}=${VAR_VALUE}" >> "$ENV_FILE"
        fi
    fi
}

inject_env "APP_NAME" "$APP_NAME"
inject_env "APP_ENV" "$APP_ENV"
inject_env "APP_DEBUG" "$APP_DEBUG"
inject_env "APP_URL" "$APP_URL"
inject_env "APP_LOCALE" "$APP_LOCALE"

inject_env "DB_CONNECTION" "$DB_CONNECTION"
inject_env "DB_HOST" "$DB_HOST"
inject_env "DB_PORT" "$DB_PORT"
inject_env "DB_DATABASE" "$DB_DATABASE"
inject_env "DB_USERNAME" "$DB_USERNAME"
inject_env "DB_PASSWORD" "$DB_PASSWORD"

inject_env "REDIS_HOST" "$REDIS_HOST"
inject_env "REDIS_PASSWORD" "$REDIS_PASSWORD"
inject_env "REDIS_PORT" "$REDIS_PORT"

inject_env "CACHE_DRIVER" "$CACHE_DRIVER"
inject_env "SESSION_DRIVER" "$SESSION_DRIVER"
inject_env "QUEUE_CONNECTION" "$QUEUE_CONNECTION"

inject_env "MAIL_MAILER" "$MAIL_MAILER"
inject_env "MAIL_HOST" "$MAIL_HOST"
inject_env "MAIL_PORT" "$MAIL_PORT"
inject_env "MAIL_USERNAME" "$MAIL_USERNAME"
inject_env "MAIL_PASSWORD" "$MAIL_PASSWORD"
inject_env "MAIL_ENCRYPTION" "$MAIL_ENCRYPTION"
inject_env "MAIL_FROM_ADDRESS" "$MAIL_FROM_ADDRESS"
inject_env "MAIL_FROM_NAME" "$MAIL_FROM_NAME"

inject_env "SENTRY_LARAVEL_DSN" "$SENTRY_LARAVEL_DSN"

inject_env "OAUTH_CLIENT_ID" "$OAUTH_CLIENT_ID"
inject_env "OAUTH_CLIENT_SECRET" "$OAUTH_CLIENT_SECRET"

# -- Generate APP_KEY if missing ----------------------------------------------
CURRENT_KEY=$(grep "^APP_KEY=" "$ENV_FILE" | cut -d= -f2-)
if [ -z "$CURRENT_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# -- Create storage symlink ---------------------------------------------------
php artisan storage:link --force 2>/dev/null || true

# -- Run database migrations --------------------------------------------------
echo "Running database migrations..."
php artisan migrate --force --no-interaction

# -- Import translations ------------------------------------------------------
echo "Importing translations..."
php artisan translations:import --locale "fr_FR" || true
php artisan translations:import --locale "en_GB" || true

# -- Clear and warm caches ----------------------------------------------------
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# -- Start cron daemon for Laravel scheduler ----------------------------------
echo "Starting cron daemon..."
crond -b -l 8

# -- Hand off to CMD (supervisord) --------------------------------------------
echo "Starting application..."
exec "$@"
