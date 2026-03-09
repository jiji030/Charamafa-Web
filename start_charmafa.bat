@echo off
title CHARMAFA System
echo Starting CHARMAFA System...
echo Network IP: 192.168.254.108
echo Frontend: https://192.168.254.108:3000
echo Backend: http://192.168.254.108:8000

echo.
echo Starting Laravel backend...
start /min cmd /k "cd /d %~dp0collector-backend && php artisan serve --host=0.0.0.0 --port=8000"

echo Waiting for backend to initialize...
timeout /t 3 /nobreak >nul

echo Starting Nuxt frontend...
start /min cmd /k "cd /d %~dp0collector-frontend && npm run dev -- --host 0.0.0.0"

echo.
echo System is starting up...
echo Frontend will be available at: https://192.168.254.108:3000
echo Backend API available at: http://192.168.254.108:8000/api

timeout /t 10

echo Opening browser...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    echo Opening with Chrome...
    "C:\Program Files\Google\Chrome\Application\chrome.exe" "https://192.168.254.108:3000"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    echo Opening with Chrome...
    "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" "https://192.168.254.108:3000"
) else (
    echo Chrome not found, opening with default browser...
    start "" "https://192.168.254.108:3000"
)

echo.
echo CHARMAFA System is now running!
echo Press any key to exit this window (servers will continue running)
pause >nul











































































































