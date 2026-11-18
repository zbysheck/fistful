@echo off
rem Jednorazowa konfiguracja serwera MyDevil dla Fistful of Ideas.
rem Wymaga pliku .env.production (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD) w katalogu projektu.
setlocal
set HOST=zbigniewkisly@s59.mydevil.net
if not exist .env.production (
    echo Brak pliku .env.production z danymi bazy.
    exit /b 1
)
scp .env.production %HOST%:fistful-db.env || exit /b 1
scp deploy/setup-server.sh %HOST%:fistful-setup.sh || exit /b 1
ssh %HOST% "bash fistful-setup.sh; rm -f fistful-db.env fistful-setup.sh" || exit /b 1
