# Running the platform with Docker

Docker runs the website and its MySQL database in two containers, on your PC or on any server with Docker. Nothing else (PHP, Composer, MySQL, Node.js) needs to be installed.

| Container | What it does |
|---|---|
| `app` | The website (PHP 8.3 + Apache). Serves only the `public/` folder |
| `db` | MySQL 8.4 database |
| `caddy` (optional) | Free HTTPS certificate for a real domain |

Your data is kept in Docker **volumes** (`db-data` for the database, including uploaded pictures; `app-storage` for logs and the app key), so it survives restarts, updates and rebuilds.

---

## 1. Install Docker

- **Windows / Mac:** install [Docker Desktop](https://www.docker.com/products/docker-desktop/) and start it.
- **Linux server (Ubuntu):** `curl -fsSL https://get.docker.com | sh`

Check it works:

```bash
docker --version
```

```bash
docker compose version
```

## 2. Get the code

```bash
git clone https://github.com/norkaid/Innes-Eye-and-Vision-Care.git
```

```bash
cd Innes-Eye-and-Vision-Care
```

## 3. Create the settings file

Windows (PowerShell):

```bash
Copy-Item .env.docker.example .env.docker
```

macOS / Linux:

```bash
cp .env.docker.example .env.docker
```

Open `.env.docker` and change at least:

| Setting | What to put |
|---|---|
| `APP_URL` | The address people will type, e.g. `http://localhost:8080` on a PC, or `https://your-domain.com` |
| `DB_PASSWORD` **and** `MYSQL_PASSWORD` | The same new password (any long random text) |
| `MYSQL_ROOT_PASSWORD` | Another long random password |
| `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` | Your Paystack keys (test keys while testing) |
| `SMS_DRIVER`, `SMS_SENDER_ID`, `ARKESEL_API_KEY` | `arkesel`, your approved sender ID (no spaces), your Arkesel key |
| `MAIL_*` | Your Gmail (app password) or Hostinger mailbox; `MAIL_MAILER=smtp` |

`.env.docker` contains secrets: Git ignores it, never share or commit it. Leave `APP_KEY` empty: a key is created automatically on first start.

**Just trying it out?** Set `APP_ENV=local` and `SEED_DEMO_DATA=true` to load the demo content and demo accounts (password: `DEMO_USER_PASSWORD`). For the real site keep `APP_ENV=production` and `SEED_DEMO_DATA=false`.

## 4. Start it

```bash
docker compose up -d --build
```

The first build takes a few minutes. On every start the app waits for the database, runs the migrations, creates the roles and caches the settings. Follow the progress with:

```bash
docker compose logs -f app
```

When you see `apache2 -D FOREGROUND` / `resuming normal operations`, open **http://localhost:8080** (or your `APP_URL`). Press `Ctrl+C` to stop following the logs; the site keeps running.

## 5. Create the first administrator

(Not needed if you loaded demo data; then sign in as `superadmin@innes.test`.)

```bash
docker compose exec app php artisan innes:create-admin
```

Sign in at `/login`.

## 6. Check the set-up

```bash
docker compose exec app php artisan innes:doctor
```

It lists anything missing (Paystack key, SMS, email…) with the fix. After changing `.env.docker`, apply it with:

```bash
docker compose up -d
```

## 7. Move your content from your PC

On your PC (the normal `php artisan serve` site): **Admin → Content transfer → Download export file**. On the Docker site: **Admin → Content transfer → Import content**. For large files:

```bash
docker compose cp innes-content-2026-11-22.json.gz app:/tmp/content.json.gz
```

```bash
docker compose exec app php artisan innes:import-content /tmp/content.json.gz --force
```

---

## Everyday commands

| Task | Command |
|---|---|
| Start | `docker compose up -d` |
| Stop | `docker compose down` (data is kept) |
| See logs | `docker compose logs -f app` |
| Run an artisan command | `docker compose exec app php artisan <command>` |
| Update to the latest code | `git pull`, then `docker compose up -d --build` |
| Back up the database | `docker compose exec db sh -c 'mysqldump --single-transaction --hex-blob -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql` |
| Restore a backup | `docker compose exec -T db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < backup.sql` |

**Never run `docker compose down -v`** unless you want to delete everything: `-v` removes the volumes, i.e. the database and all uploaded pictures.

## Using a different port

If port 8080 is taken, start with another one (and set `APP_URL` to match):

```bash
APP_PORT=8090 docker compose up -d
```

On Windows PowerShell: `$env:APP_PORT=8090; docker compose up -d`.

## Going live with HTTPS on a server

1. Point your domain's DNS **A record** at the server's IP address.
2. In `.env.docker` set:
   ```
   DOMAIN=your-domain.com
   APP_URL=https://your-domain.com
   FORCE_HTTPS=true
   SESSION_SECURE_COOKIE=true
   ```
3. Open ports 80 and 443 in the server firewall, then start with the HTTPS profile:
   ```bash
   docker compose --profile https up -d --build
   ```
   Caddy gets a free certificate automatically. The app's own port (8080) does not need to be open to the internet.
4. In the Paystack dashboard set the webhook URL to `https://your-domain.com/paystack/webhook`, and switch to live keys when the business is ready to take real money.

## Troubleshooting

| Problem | Fix |
|---|---|
| `port is already allocated` | Use another port (see above) |
| App keeps restarting with "Database not reachable" | `DB_PASSWORD` and `MYSQL_PASSWORD` must match. If you changed them after the first start, the old password is stored in the `db-data` volume: put the old one back, or (only if there is no data yet) `docker compose down -v` and start again |
| Page shows *500 Server Error* | `docker compose logs app` shows the reason |
| Changes to `.env.docker` have no effect | Run `docker compose up -d` (it recreates the container with the new settings) |
| Login keeps returning to the login page | On plain `http://`, `SESSION_SECURE_COOKIE` must be `false` |
