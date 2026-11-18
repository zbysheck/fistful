#!/usr/bin/env bash
# Uruchamiany NA SERWERZE przez deploy.bat. Aktualizuje kod i aplikacje.
set -euo pipefail

cd "$(dirname "$0")/.."

git pull --ff-only

# Znajduje Composera 2 (na MyDevil bywa pod roznymi nazwami); w ostatecznosci pobiera composer.phar.
find_composer() {
    local c
    for c in composer2 composer /usr/local/bin/composer2 /usr/local/bin/composer "$HOME/bin/composer"; do
        if command -v "$c" >/dev/null 2>&1 && "$c" --version 2>/dev/null | grep -Eq 'version 2\.'; then
            echo "$c"
            return
        fi
    done
    mkdir -p "$HOME/bin"
    if [ ! -f "$HOME/bin/composer.phar" ]; then
        php -r 'copy("https://getcomposer.org/download/latest-2.x/composer.phar", getenv("HOME")."/bin/composer.phar") || exit(1);' >&2 || return 1
    fi
    echo "php $HOME/bin/composer.phar"
}
COMPOSER=$(find_composer)

$COMPOSER install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
[ -L public/storage ] || php artisan storage:link
php artisan optimize

echo "Deploy OK: $(git log -1 --format='%h %s')"
