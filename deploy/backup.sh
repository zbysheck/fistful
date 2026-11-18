#!/usr/bin/env bash
# Codzienna kopia bazy (uruchamiana z crona na serwerze). Trzyma 14 ostatnich kopii poza katalogiem strony.
set -euo pipefail

cd "$(dirname "$0")/.."

env_value() {
    grep -E "^$1=" .env | tail -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/'
}

dir="$HOME/fistful-backups"
mkdir -p "$dir"
chmod 700 "$dir"

file="$dir/db-$(date +%Y-%m-%d).sql.gz"

MYSQL_PWD="$(env_value DB_PASSWORD)" mysqldump \
    --single-transaction --no-tablespaces \
    -h "$(env_value DB_HOST)" -u "$(env_value DB_USERNAME)" "$(env_value DB_DATABASE)" | gzip > "$file.tmp"
mv "$file.tmp" "$file"

find "$dir" -name 'db-*.sql.gz' -mtime +14 -delete
