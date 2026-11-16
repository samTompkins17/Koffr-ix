#!/bin/sh
# Prépare l'environnement du service au démarrage.
#
# Pourquoi ce script : `php artisan serve` transmet à son processus fils les
# valeurs du fichier .env, qui prennent alors le dessus sur les variables
# Docker Compose. On recopie donc les variables d'infrastructure (base, JWT)
# dans .env : Compose reste la source de vérité.
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

for cle in APP_URL APP_ENV APP_KEY DB_CONNECTION DB_HOST DB_PORT DB_DATABASE \
           DB_USERNAME DB_PASSWORD JWT_SECRET JWT_TTL ALLOWED_EMAIL_DOMAIN \
           PUBLIC_VERIFY_URL PATRIMOINE_URL GATEWAY_URL SESSION_DRIVER CACHE_STORE; do
    valeur=$(printenv "$cle" 2>/dev/null || true)
    [ -n "$valeur" ] || continue
    if grep -q "^${cle}=" .env; then
        sed -i "s|^${cle}=.*|${cle}=${valeur}|" .env
    else
        printf '%s=%s\n' "$cle" "$valeur" >> .env
    fi
done

# Clé applicative générée une seule fois (sessions, cookies chiffrés).
if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true
fi

# Migrations et données de démonstration, pilotées par des variables.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan migrate --force --no-interaction
fi
if [ "${RUN_SEED:-0}" = "1" ]; then
    php artisan db:seed --force --no-interaction
fi

exec "$@"
