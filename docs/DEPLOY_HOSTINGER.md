# Deploying to Hostinger

These steps target **Hostinger web hosting with SSH access** (Premium, Business or Cloud plans). A VPS works too but needs its own server setup.

Throughout, replace `example.com` with your domain and `u123456789` with your Hostinger username (shown in hPanel → **Advanced → SSH Access**).

Built CSS/JS (`public/build`) is committed to the repository, so **Node.js is not needed on the server**. If you change styles or scripts, run `npm run build` locally and commit the result.

---

## 1. Prepare hPanel

1. **PHP version:** hPanel → **Advanced → PHP Configuration** → select **PHP 8.3** (or newer). Under *PHP Extensions*, make sure `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl` and `zip` are enabled.
2. **Database:** hPanel → **Databases → MySQL Databases** → create a database and user. Write down the full names (they look like `u123456789_innes` and `u123456789_innesuser`) and the password. The host is `localhost`.
3. **SSH:** hPanel → **Advanced → SSH Access** → enable it and note the IP, port and username.
4. **SSL:** hPanel → **Security → SSL** → install the free SSL certificate for your domain.
5. **Email (optional, for notifications):** hPanel → **Emails** → create a mailbox such as `no-reply@example.com`.

## 2. Get the code on the server

Connect via SSH (Hostinger uses a non-standard port such as 65002):

```bash
ssh -p 65002 u123456789@YOUR_SERVER_IP
```

Clone the repository into a folder **next to** `public_html` (not inside it):

```bash
cd ~/domains/example.com
git clone -b claude/affectionate-pascal-u5lahv https://github.com/norkaid/Innes-Eye-and-Vision-Care.git innes
```

If the repository is private, create a GitHub **personal access token** (read-only, this repo) and use `https://<TOKEN>@github.com/norkaid/Innes-Eye-and-Vision-Care.git`, or add the server's SSH key as a deploy key in GitHub → repo → Settings → Deploy keys.

Check the PHP version used on the command line (it must be 8.3+):

```bash
php -v
```

If it shows an older version, use the full path instead, e.g. `/opt/alt/php83/usr/bin/php`, in all commands below.

## 3. Install and configure

```bash
cd ~/domains/example.com/innes
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env
```

Set at least these values in `.env` (`Ctrl+O` to save, `Ctrl+X` to exit):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_innes
DB_USERNAME=u123456789_innesuser
DB_PASSWORD=your-database-password

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@example.com
MAIL_PASSWORD=your-mailbox-password
MAIL_FROM_ADDRESS=no-reply@example.com

SHOW_DEMO_NOTICE=true
DEMO_USER_PASSWORD=
```

If you haven't set up email yet, use `MAIL_MAILER=log`. The site will then correctly say that no email acknowledgement was sent.

Create the tables and roles (in production this does **not** add demo content):

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Create the first Super Admin. You'll be asked for name, email and a strong password; no default password exists:

```bash
php artisan innes:create-admin
```

Cache the configuration for speed:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Point the domain at Laravel's `public` folder

The website must be served from `innes/public`, never from the project root (that would expose `.env`).

**Option A – symbolic link (recommended).** Back up anything already in `public_html`, then:

```bash
cd ~/domains/example.com
mv public_html public_html_old
ln -s innes/public public_html
```

**Option B – if a symlink isn't allowed.** Keep `public_html` and create `public_html/.htaccess` containing:

```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ ../innes/public/$1 [L]
</IfModule>
```

Some Hostinger setups block rewriting outside `public_html`. If Option B shows an error, use Option A or ask Hostinger support to set the domain's document root to `domains/example.com/innes/public`.

## 5. Test

1. Open `https://example.com`: the home page should load over HTTPS.
2. Sign in at `https://example.com/login` with the admin you created.
3. Go to **Admin → Settings** and enter the official business details.
4. Submit a test appointment request on the public site and confirm it appears under **Admin → Appointments**.
5. Send a test enquiry from **Contact**.
6. If email is configured: set a *Staff alert email* in **Settings**, submit another request and check the inbox.

The live database starts **empty** apart from roles. Add real branches (with opening hours), services, product categories, products and articles in the admin before people can book. Booking stays unavailable until at least one active branch offers at least one published service.

When all content is verified, set `SHOW_DEMO_NOTICE=false` in `.env`, then run:

```bash
php artisan config:cache
```

## 6. Updating the site later

After new code has been pushed to GitHub:

```bash
cd ~/domains/example.com/innes
bash deploy.sh
```

This puts the site into maintenance mode, pulls the code, installs packages, runs migrations, refreshes the caches and brings the site back up.

## 7. Backups

- hPanel → **Files → Backups** gives automatic daily or weekly backups, depending on your plan. Check that they include the database.
- Also export the database regularly: hPanel → **Databases → phpMyAdmin** → *Export*. Keep a copy off the server.
- Keep a private copy of the production `.env`; it contains the `APP_KEY` needed to read encrypted sessions.
- Uploaded images live in `innes/storage/app/public`; include this folder in your backups.

## Troubleshooting

| Problem | Fix |
|---|---|
| "500 Server Error" | Check `innes/storage/logs/laravel.log`. Run `php artisan config:clear` after any `.env` edit, then `php artisan config:cache`. |
| Blank page or the directory listing shows | The domain isn't pointing at `innes/public` (step 4). |
| Images upload but don't display | Run `php artisan storage:link` again. |
| `composer` or `php` version errors | Choose PHP 8.3 in hPanel and use the matching CLI binary (see step 2). |
| Redirect loop on HTTPS | Set `TRUSTED_PROXIES=*` in `.env`, then run `php artisan config:cache`. |
| Styling missing | Make sure `public/build` exists in the repository and `APP_URL` matches the domain exactly (https, with or without www). |
