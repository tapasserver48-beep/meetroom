# Railway Deployment Guide

## Quick Deploy (2 minutes)

### 1. Connect Repository
1. Go to [railway.app](https://railway.app) and sign in
2. Click **"New Project"** → **"Deploy from GitHub repo"**
3. Select `tapasserver48-beep/meetroom`
4. Click **Deploy Now**

### 2. Configure Environment Variables
In Railway dashboard → your project → **Variables** tab, add:

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_KEY` | `base64:...` | Generate locally: `php artisan key:generate --show` |
| `APP_URL` | `https://your-app.up.railway.app` | Your Railway domain |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `DB_CONNECTION` | `sqlite` | Or `mysql` with Railway MySQL plugin |
| `DB_DATABASE` | `database/database.sqlite` | For SQLite |
| `BROADCAST_CONNECTION` | `reverb` | |
| `QUEUE_CONNECTION` | `sync` | |
| `REVERB_APP_ID` | `local` | |
| `REVERB_APP_KEY` | `local` | |
| `REVERB_APP_SECRET` | `local` | |
| `REVERB_HOST` | `0.0.0.0` | |
| `REVERB_PORT` | `8080` | |
| `REVERB_SCHEME` | `http` | |
| `REVERB_PUBLIC_WS_URL` | `your-app.up.railway.app` | Your Railway domain |
| `CLOUDFLARE_TURN_KEY_ID` | `d620458570a991df270da6f309d52e3b` | |
| `CLOUDFLARE_TURN_KEY_SECRET` | `2c362dbe9099c5fe1a7ec2e3b020d888eaaa8b54754239c21a586a5a2a8ffda7` | |

### 3. Add Railway MySQL (Optional)
If you prefer MySQL over SQLite:
1. In Railway dashboard → **New** → **Database** → **MySQL**
2. Copy the generated variables: `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`
3. Set in variables:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=${MYSQLHOST}`
   - `DB_PORT=${MYSQLPORT}`
   - `DB_DATABASE=${MYSQLDATABASE}`
   - `DB_USERNAME=${MYSQLUSER}`
   - `DB_PASSWORD=${MYSQLPASSWORD}`

### 4. Deploy
Railway auto-deploys on every push to `master`. Just push to GitHub:

```bash
git add .
git commit -m "Deploy to Railway"
git push origin master
```

### 5. Verify Deployment
1. Check Railway logs for successful build
2. Visit your app URL: `https://your-app.up.railway.app`
3. Test: Create meeting → Join as host → Open incognito → Join as guest → Verify video/screen share

### Custom Domain (Optional)
1. Railway → Settings → **Domains** → **Custom Domain**
2. Add your domain → Configure DNS (CNAME to `your-app.up.railway.app`)

## Files Added for Railway
- `nixpacks.toml` - Build/install/start commands
- `railway.json` - Railway-specific config
- `Dockerfile` + `docker/` - Docker alternative
- `.railwayignore` / `.dockerignore` - Ignore unnecessary files

## Auto-Deploy
Every `git push origin master` triggers a new Railway deployment automatically.