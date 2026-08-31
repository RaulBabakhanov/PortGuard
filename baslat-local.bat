@echo off
chcp 65001 >nul
cd /d "%~dp0"

set PHP=C:\wamp64\bin\php\php8.3.14\php.exe
if not exist "%PHP%" (
  echo WAMP PHP 8.3 bulunamadi: %PHP%
  echo XAMPP PHP 8.2 bu proje icin yeterli degil.
  pause
  exit /b 1
)

REM Laravel .env uzerine yazmaz; mevcut ortam degiskeni onceliklidir
set APP_URL=http://127.0.0.1:8000
set APP_ENV=local

echo.
echo PortGuard local: http://127.0.0.1:8000
echo (XAMPP http://localhost/PortGuard PHP 8.2 ile CALISMAZ)
echo Durdurmak icin Ctrl+C
echo.

"%PHP%" artisan config:clear
"%PHP%" artisan serve --host=127.0.0.1 --port=8000
pause
