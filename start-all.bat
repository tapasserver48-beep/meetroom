@echo off
REM Auto-start script: Laravel + Reverb + Cloudflare Tunnel (Windows)

cd /d "D:\xampp\htdocs\ac- meetx\meetroom"

echo 🚀 Starting MeetRoom services...

echo Starting Laravel on port 8000...
start "Laravel" /B php artisan serve --host=0.0.0.0 --port=8000

timeout /t 3 /nobreak >nul

echo Starting Reverb on port 8080...
start "Reverb" /B php artisan reverb:start --host=0.0.0.0 --port=8080

timeout /t 2 /nobreak /nobreak >nul

echo Starting Cloudflare Tunnel...
start "Cloudflare" /B cloudflared tunnel --url http://localhost:8000

timeout /t 5 /nobreak >nul

echo.
echo ✅ All services started!
echo.
echo 🌍 Your public URL will appear in the Cloudflare window above
echo 📝 Look for: https://random-name.trycloudflare.com
echo.
echo Press any key to stop all services...
pause >nul

taskkill /F /FI "WINDOWTITLE eq Laravel*"
taskkill /F /FI "WINDOWTITLE eq Reverb*"
taskkill /F /FI "WINDOWTITLE eq Cloudflare*"