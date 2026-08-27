#!/bin/bash
# Auto-start script: Laravel + Reverb + Cloudflare Tunnel

set -e

cd /var/www/html/meetroom

echo "🚀 Starting MeetRoom services..."

# Start Laravel
echo "Starting Laravel on port 8000..."
php artisan serve --host=0.0.0.0 --port=8000 > /var/log/laravel.log 2>&1 &
LARAVEL_PID=$!

# Wait for Laravel to be ready
sleep 3

# Start Reverb
echo "Starting Reverb on port 8080..."
php artisan reverb:start --host=0.0.0.0 --port=8080 > /var/log/reverb.log 2>&1 &
REVERB_PID=$!

sleep 2

# Start Cloudflare Tunnel
echo "Starting Cloudflare Tunnel..."
CLOUDFLARE_OUTPUT=$(cloudflared tunnel --url http://localhost:8000 2>&1 &
CLOUDFLARE_PID=$!)

# Extract tunnel URL
sleep 5
TUNNEL_URL=$(grep -o 'https://[a-zA-Z0-9-]*\.trycloudflare\.com' /dev/stdout 2>/dev/null | head -1)

if [ -z "$TUNNEL_URL" ]; then
    # Try getting from cloudflared output
    TUNNEL_URL=$(ps aux | grep cloudflared | head -1)
fi

echo ""
echo "✅ All services started!"
echo "Laravel PID: $LARAVEL_PID"
echo "Reverb PID: $REVERB_PID"
echo "Cloudflare PID: $CLOUDFLARE_PID"
echo ""
echo "🌍 Your public URL will be shown in cloudflared logs above"
echo "📝 Check logs: tail -f /var/log/laravel.log /var/log/reverb.log"
echo ""

# Keep running
wait $CLOUDFLARE_PID