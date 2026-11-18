@echo off
rem Uruchamia runner, przez ktory Claude odpala komendy projektu. Zamknij okno, zeby go wylaczyc.
cd /d "%~dp0.."
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0claude-runner.ps1"
