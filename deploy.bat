@echo off
rem Deploy na serwer (MyDevil). Uruchamiaj z katalogu projektu: deploy.bat
rem Ustawienia serwera sa w deploy\project.env (laravel-wizard).
setlocal
cd /d "%~dp0"
for /f "usebackq eol=# tokens=1,* delims==" %%a in ("deploy\project.env") do set "%%a=%%b"

git push -u origin HEAD || exit /b 1
if exist package.json (
    call npm run build || exit /b 1
    scp -r public/build %SSH_HOST%:%APP_DIR%/public/ || exit /b 1
)
ssh %SSH_HOST% "bash %APP_DIR%/deploy/server.sh" || exit /b 1
