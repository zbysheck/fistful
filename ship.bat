@echo off
rem Formatowanie, build, testy, commit, push i deploy jedna komenda: ship "opis zmiany"
rem Wynik sprawdzen trafia do storage\logs\ship.txt, zeby Claude mogl go przeczytac.
setlocal
if "%~1"=="" (
    echo Uzycie: ship "opis zmiany"
    exit /b 1
)

set LOG=storage\logs\ship.txt
echo Sprawdzam kod (formatowanie, build, testy)...
call composer lint > %LOG% 2>&1 || goto fail
call npm run check:fix >> %LOG% 2>&1 || goto fail
call npm run build >> %LOG% 2>&1 || goto fail
call composer ci:check >> %LOG% 2>&1 || goto fail
echo Sprawdzenia OK.

git add -A
git diff --cached --quiet || git commit -m "%~1" || exit /b 1
call deploy.bat
exit /b %errorlevel%

:fail
type %LOG%
echo.
echo Sprawdzenia nie przeszly. Pelny wynik: %LOG% - napisz Claude, ze padlo.
exit /b 1
