@echo off
rem Ustawia zmienna w .env na serwerze: deploy\set-env NAZWA "wartosc"
setlocal
set HOST=zbigniewkisly@s59.mydevil.net
set APP=domains/fistfulofideas.zbigniewkisly.usermd.net/app

if "%~1"=="" (
    echo Uzycie: deploy\set-env NAZWA "wartosc"
    exit /b 1
)

ssh %HOST% "bash %APP%/deploy/set-env.sh %~1 '%~2'"
