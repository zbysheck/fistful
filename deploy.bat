@echo off
rem Deploy na staging (MyDevil). Uruchamiaj z katalogu projektu: deploy.bat
setlocal
set HOST=zbigniewkisly@s59.mydevil.net
set APP=domains/fistfulofideas.zbigniewkisly.usermd.net/app

git push || exit /b 1
call npm run build || exit /b 1
scp -r public/build %HOST%:%APP%/public/ || exit /b 1
ssh %HOST% "bash %APP%/deploy/server.sh" || exit /b 1
