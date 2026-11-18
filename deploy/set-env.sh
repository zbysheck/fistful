#!/usr/bin/env bash
# Uruchamiany NA SERWERZE: ustawia jedna zmienna w .env i odswieza konfiguracje.
# Uzycie: bash deploy/set-env.sh NAZWA "wartosc"
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! "${1:-}" =~ ^[A-Z][A-Z0-9_]*$ ]]; then
    echo "Podaj nazwe zmiennej wielkimi literami, np. GEMINI_MODEL"
    exit 1
fi

php -r '
$file = ".env";
[$key, $value] = [$argv[1], $argv[2]];
$line = $key."=".$value;
$env = file_get_contents($file);
$count = 0;
$env = preg_replace_callback("/^#?\s*".preg_quote($key, "/")."=.*$/m", fn () => $line, $env, 1, $count);
if ($count === 0) {
    $env = rtrim($env).PHP_EOL.$line.PHP_EOL;
}
file_put_contents($file, $env);
' "$1" "${2:-}"

php artisan optimize >/dev/null
echo "Ustawiono $1 na serwerze."
