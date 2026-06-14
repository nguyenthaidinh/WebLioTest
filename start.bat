@echo off
title Chu Be Rong Online - Web Server
color 0A

echo ============================================
echo   CHU BE RONG ONLINE - Web Server
echo ============================================
echo.
echo   Dang khoi dong web tai: http://localhost:9000
echo   Nhan Ctrl+C de dung server
echo.
echo ============================================
echo.

php -S localhost:9000 -t "%~dp0." "%~dp0router.php"

pause
