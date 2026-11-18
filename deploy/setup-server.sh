#!/usr/bin/env bash
# laravel-wizard: konfiguracja projektu na MyDevil. Uruchamiane przez runner (akcja setup-server / new).
# Ustawienia czyta z ~/.wizard/<nazwa>.env (kopia deploy/project.env).
#   bash setup-server.sh key <nazwa>       -> tworzy deploy key i wypisuje klucz publiczny
#   bash setup-server.sh install <nazwa>   -> strona, baza, kod, .env, migracje (haslo bazy na stdin)
# Mozna odpalac wielokrotnie: pomija kroki, ktore sa juz zrobione.
set -euo pipefail

PHASE=${1:?faza: key albo install}
NAME=${2:?nazwa projektu}
CONF=$HOME/.wizard/$NAME.env
[ -f "$CONF" ] || { echo "Brak $CONF"; exit 1; }
# shellcheck disable=SC1090
set -a; . "$CONF"; set +a

KEY=$HOME/.ssh/${NAME}_deploy
DIR=$HOME/domains/$DOMAIN

if [ "$PHASE" = key ]; then
    mkdir -p "$HOME/.ssh" && chmod 700 "$HOME/.ssh"
    [ -f "$KEY" ] || ssh-keygen -q -t ed25519 -f "$KEY" -N "" -C "${NAME}-deploy"
    cat "$KEY.pub"
    exit 0
fi

[ "$PHASE" = install ] || { echo "Nieznana faza: $PHASE"; exit 1; }

read -r DB_PASSWORD || true
DB_PASSWORD=$(printf '%s' "$DB_PASSWORD" | tr -d '\r')
export DB_PASSWORD

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

# Odpowiada na pytania devila o haslo (np. "Haslo:", "Powtorz haslo:").
devil_with_password() {
    local exp
    exp=$(mktemp)
    cat > "$exp" <<'EXP'
set timeout 60
spawn {*}$argv
expect {
    -nocase -re {(has|pass)[^\n*]*:\s*$} { send -- "$env(PW)\r"; exp_continue }
    eof
}
catch wait result
exit [lindex $result 3]
EXP
    local rc=0
    PW="$DB_PASSWORD" expect "$exp" "$@" || rc=$?
    rm -f "$exp"
    return $rc
}

# Ustawia wartosci w .env: WIZ_MODE=app (APP_*) albo db (DB_*).
SET_ENV_PHP='
$quote = fn (string $v): string => "\"".addcslashes($v, "\\\"$")."\"";
$values = getenv("WIZ_MODE") === "db" ? [
    "DB_CONNECTION" => "mysql",
    "DB_HOST" => getenv("DB_HOST"),
    "DB_PORT" => "3306",
    "DB_DATABASE" => getenv("DB_DATABASE"),
    "DB_USERNAME" => getenv("DB_USERNAME"),
    "DB_PASSWORD" => $quote(getenv("DB_PASSWORD")),
] : [
    "APP_NAME" => $quote(getenv("NAME")),
    "APP_ENV" => "production",
    "APP_DEBUG" => "false",
    "APP_URL" => getenv("APP_URL"),
];
$env = file_get_contents(".env");
foreach ($values as $key => $value) {
    $line = $key."=".$value;
    $count = 0;
    $env = preg_replace_callback("/^#?\s*".preg_quote($key, "/")."=.*$/m", fn () => $line, $env, 1, $count);
    if ($count === 0) {
        $env = rtrim($env).PHP_EOL.$line.PHP_EOL;
    }
}
file_put_contents(".env", $env);
'

echo "== 1/6 Strona WWW ($DOMAIN) =="
if [ ! -d "$DIR" ]; then
    devil www add "$DOMAIN" php || { echo "Nie udalo sie dodac strony $DOMAIN."; exit 1; }
else
    echo "Juz jest."
fi

echo "== 2/6 Kod =="
export GIT_SSH_COMMAND="ssh -i $KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
cd "$DIR"
if [ ! -d app/.git ]; then
    tries=0
    until git ls-remote "$REPO" >/dev/null 2>&1; do
        tries=$((tries + 1))
        [ $tries -ge 6 ] && { echo "GitHub nie przyjmuje deploy key dla $REPO."; exit 1; }
        sleep 5
    done
    git clone -q "$REPO" app
fi
git -C app config core.sshCommand "ssh -i $KEY -o IdentitiesOnly=yes"
if [ ! -L public_html ]; then
    [ -e public_html ] && mv public_html "public_html.bak.$(date +%s)"
    ln -s app/public public_html
fi
devil www options "$DOMAIN" php_openbasedir "$DIR/app/" >/dev/null || true

echo "== 3/6 Zaleznosci PHP =="
cd "$DIR/app"
COMPOSER=$(find_composer)
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "== 4/6 Plik .env =="
export APP_URL="https://$DOMAIN"
NEW_ENV=0
if [ ! -f .env ]; then
    cp .env.example .env
    chmod 600 .env
    NEW_ENV=1
    WIZ_MODE=app php -r "$SET_ENV_PHP"
    php artisan key:generate --force
else
    echo ".env juz jest."
fi

echo "== 5/6 Baza MySQL ($DB_DATABASE) =="
db_works() {
    php -r '
$env = [];
foreach (file(".env", FILE_IGNORE_NEW_LINES) as $l) {
    if (preg_match("/^(DB_[A-Z_]+)=(.*)$/", $l, $m)) { $env[$m[1]] = trim($m[2], "\""); }
}
try {
    new PDO("mysql:host={$env["DB_HOST"]};dbname={$env["DB_DATABASE"]}", $env["DB_USERNAME"], stripcslashes($env["DB_PASSWORD"] ?? ""));
} catch (Throwable $e) { exit(1); }
' 2>/dev/null
}
if [ "$NEW_ENV" = 0 ] && grep -q '^DB_CONNECTION=mysql' .env && db_works; then
    echo "Baza dziala."
else
    [ -n "$DB_PASSWORD" ] || { echo "Brak hasla bazy na wejsciu."; exit 1; }
    if devil mysql list 2>/dev/null | grep -q "^$DB_DATABASE "; then
        echo "Baza istnieje - ustawiam nowe haslo uzytkownika."
        devil_with_password devil mysql passwd "$DB_USERNAME"
    else
        devil_with_password devil mysql db add "$DB_DATABASE" "$DB_USERNAME"
    fi
    WIZ_MODE=db php -r "$SET_ENV_PHP"
    db_works || { echo "Nadal nie moge polaczyc sie z baza $DB_DATABASE."; exit 1; }
    echo "Baza gotowa, dane w .env."
fi

echo "== 6/6 Migracje i cache =="
php artisan migrate --force
[ -L public/storage ] || php artisan storage:link
php artisan optimize

echo "Setup serwera OK: https://$DOMAIN"
