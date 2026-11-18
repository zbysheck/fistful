#!/usr/bin/env bash
# Jednorazowa konfiguracja na MyDevil (uruchamiana przez deploy\setup.bat). Mozna odpalac wielokrotnie.
set -euo pipefail

DOMAIN=fistfulofideas.zbigniewkisly.usermd.net
REPO=https://github.com/zbysheck/fistful.git
DIR=$HOME/domains/$DOMAIN
DBENV=$HOME/fistful-db.env

find_composer() {
    local c
    for c in composer2 composer /usr/local/bin/composer2 /usr/local/bin/composer "$HOME/bin/composer"; do
        if command -v "$c" >/dev/null 2>&1 && "$c" --version 2>/dev/null | grep -Eq 'version 2\.'; then
            echo "$c"; return
        fi
    done
    mkdir -p "$HOME/bin"
    [ -f "$HOME/bin/composer.phar" ] || php -r 'copy("https://getcomposer.org/download/latest-2.x/composer.phar", getenv("HOME")."/bin/composer.phar") || exit(1);' >&2
    echo "php $HOME/bin/composer.phar"
}
COMPOSER=$(find_composer)

echo "== 1/5 Strona WWW =="
[ -d "$DIR" ] || devil www add "$DOMAIN" php

echo "== 2/5 Kod =="
cd "$DIR"
if [ ! -d app/.git ]; then
    rm -rf app
    git clone "$REPO" app
fi
if [ ! -L public_html ]; then
    [ -e public_html ] && mv public_html "public_html.bak.$(date +%s)"
    ln -s app/public public_html
fi
devil www options "$DOMAIN" php_openbasedir "$DIR/app/" || true

echo "== 3/5 Zaleznosci PHP =="
cd "$DIR/app"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "== 4/5 Plik .env =="
if [ ! -f .env ]; then
    cp .env.example .env
    chmod 600 .env
    APP_URL="https://$DOMAIN" DBENV="$DBENV" php -r '
$db = [];
foreach (file(getenv("DBENV"), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (preg_match("/^\s*([A-Z_]+)\s*=\s*(.*?)\s*$/", $l, $m)) { $db[$m[1]] = trim($m[2], "\"\x27"); }
}
$quote = fn (string $v): string => "\"".addcslashes($v, "\\\"\$")."\"";
$values = [
    "APP_ENV" => "production", "APP_DEBUG" => "false", "APP_URL" => getenv("APP_URL"),
    "DB_CONNECTION" => "mysql", "DB_HOST" => $db["DB_HOST"] ?? "", "DB_PORT" => "3306",
    "DB_DATABASE" => $db["DB_DATABASE"] ?? "", "DB_USERNAME" => $db["DB_USERNAME"] ?? "",
    "DB_PASSWORD" => $quote($db["DB_PASSWORD"] ?? ""), "LOG_LEVEL" => "warning",
];
$env = file_get_contents(".env");
foreach ($values as $k => $v) {
    $line = $k."=".$v; $n = 0;
    $env = preg_replace_callback("/^#?\s*".preg_quote($k, "/")."=.*$/m", fn () => $line, $env, 1, $n);
    if ($n === 0) { $env = rtrim($env).PHP_EOL.$line.PHP_EOL; }
}
file_put_contents(".env", $env);'
    php artisan key:generate --force
else
    echo ".env juz istnieje, zostawiam."
fi

echo "== 5/5 Baza i cache =="
php artisan migrate --force
php artisan optimize
echo "Gotowe: https://$DOMAIN"
