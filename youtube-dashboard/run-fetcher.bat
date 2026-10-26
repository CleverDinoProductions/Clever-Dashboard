@echo off
cd /d "%~dp0"
if not exist logs mkdir logs
php fetch-youtube.php >> logs\fetch.log 2>&1
exit /b %errorlevel%
