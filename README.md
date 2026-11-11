# Innes Eye & Visioncare Centre – Website & Management Platform

A web platform for **Innes Eye & Visioncare Centre, Ghana**. It combines a public website, online appointment requests and enquiries, an optional customer account area, and a secure staff dashboard with role-based access.

> **Status:** working MVP running on **demo content**. Every branch, service, product, article and contact detail shown is sample data marked **DEMO DATA — REPLACE BEFORE PRODUCTION**. See [docs/DEMO_CONTENT.md](docs/DEMO_CONTENT.md).

The original briefs are in [docs/PROJECT_BRIEF.md](docs/PROJECT_BRIEF.md) and [docs/DEVELOPMENT_BRIEF.md](docs/DEVELOPMENT_BRIEF.md). Architecture notes are in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## Features

**Public website:** Home, About, Services (search + branch filter, detail pages), Optical catalogue (category filter, keyword search, pagination, product enquiry), Branches (hours, services, directions), Eye-health articles (categories, Markdown), Contact form, Privacy notice (draft), sitemap.xml, robots.txt and SEO/Open Graph metadata.

**Appointment requests (`/appointments/book`):**
- The form shows only times that are genuinely available. They are calculated from branch opening hours, minimum notice, the booking window, slot length, per-slot capacity and existing *confirmed* appointments.
- Every submission is stored as **pending**. The confirmation page shows a hard-to-guess reference (e.g. `IEV-7KQ2MX9P`) and states clearly that the appointment is **not yet confirmed**.
- An email acknowledgement is only claimed if it was actually sent.

**Customer accounts (optional, `FEATURE_CUSTOMER_ACCOUNTS`):** register/sign in, password reset, profile, own appointment requests (cancel or request a reschedule within the business cut-off), own enquiries. Ownership is checked on the server.

**Admin dashboard (`/admin`):**

| Module | What it does |
|---|---|
| Dashboard | Live counts from the database, oldest requests waiting, today's confirmed list, recent enquiries and recent activity, filtered by role |
| Appointments | Search and filter (status/branch/service/date); confirm, propose a new time, decline, cancel, complete, no-show; capacity-checked confirmations; private notes; full status history |
| Enquiries | Filter, assign, set status (new / in progress / resolved / spam), private notes |
| Customers | Contact details and appointment history; edit; deactivate |
| Services / Products / Categories / Branches / Articles | Create, edit, publish/unpublish, archive and restore; image uploads; branch↔service links; opening hours; article draft → preview → publish |
| Staff & roles | Create and deactivate staff, assign roles (privilege-escalation safe) |
| Settings | Business details and About copy; appointment rules and notification settings (Super Admin only) |
| Reports | Date-filtered totals; CSV export contains counts only, no personal data |
| Activity log | Audit trail of administrative actions |

### Roles

| Role | Access |
|---|---|
| Super Admin | Everything, including staff management and booking-rule settings |
| Administrator | Appointments, enquiries, customers, all content, business details, reports, activity log |
| Receptionist | Appointments, enquiries, customer details |
| Content Manager | Services, products, categories, articles (no appointment or customer data) |
| Customer | Own profile, own requests and enquiries |
| Guest | Public pages and public forms |

Permissions are defined in `config/innes.php` and checked with Gates on every route and action.

---

## Tech stack

- PHP 8.3+, **Laravel 13**, Eloquent, Form validation, Gates/Policies
- Blade components, **Tailwind CSS 4**, **Alpine.js** (Livewire was not needed)
- **MySQL 8.0+** (developed against MariaDB 10.11; tests also run on SQLite)
- PHPUnit (94 tests), Laravel Pint, Vite

## Prerequisites

PHP ≥ 8.3 (extensions: pdo_mysql, mbstring, openssl, fileinfo, gd or imagick, intl), Composer 2, Node.js ≥ 20 with npm, and MySQL 8.0+.

## Local installation

Install dependencies:

```bash
composer install
```

```bash
npm install
```

Create your environment file and application key:

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

Create a MySQL database and user, then set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`. For local HTTP development also set `FORCE_HTTPS=false`.

Run the migrations, seed demo data, link storage and build the assets:

```bash
php artisan migrate --seed
```

```bash
php artisan storage:link
```

```bash
npm run build
```

```bash
php artisan serve
```

Open http://localhost:8000.

### Demo accounts (local only)

The demo seeder runs only when `APP_ENV` is not `production`. The password for every demo account is the value of `DEMO_USER_PASSWORD` in `.env` (default `Demo-Password-2026`).

| Email | Role |
|---|---|
| superadmin@innes.test | Super Admin |
| admin@innes.test | Administrator |
| reception@innes.test | Receptionist |
| content@innes.test | Content Manager |
| customer@innes.test | Customer |

Emails are written to `storage/logs/laravel.log` while `MAIL_MAILER=log`.

## Tests

```bash
php artisan test
```

By default the tests use in-memory SQLite. To run them against MySQL, point to a separate, empty test database:

```bash
DB_CONNECTION=mysql DB_DATABASE=innes_testing php artisan test
```

Code style check:

```bash
vendor/bin/pint --test
```

The tests cover public pages and visibility of drafts, search and filters, booking validation (past dates, closed days, lead time, slot grid, branch/service combinations, spam, rate limits), pending status, status transitions and history, capacity conflicts, the role matrix, guest and customer exclusion from admin, customer ownership checks, cut-off rules, CRUD for services, products, branches and articles, upload validation, staff privilege escalation, CSRF, output escaping, no exposure of sensitive data, and no committed secrets.

---

## Deployment (any PHP host)

1. **Server:** PHP 8.3+, MySQL 8, HTTPS certificate, web root pointing at `public/`, with `storage/` and `bootstrap/cache/` writable by the web server.
2. **Code:**

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   ```bash
   npm ci && npm run build
   ```

   Alternatively, build the assets locally and upload `public/build`.
3. **`.env`:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, real `MAIL_*` SMTP settings, and a least-privilege `DB_USERNAME` with rights only on this database. Never commit `.env`.
4. **Database and setup:**

   ```bash
   php artisan key:generate
   ```

   ```bash
   php artisan migrate --force
   ```

   ```bash
   php artisan db:seed --force
   ```

   In production `db:seed` creates the roles only. Then link storage and cache the configuration:

   ```bash
   php artisan storage:link
   ```

   ```bash
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

5. **First administrator.** No default password exists. Either run the interactive command:

   ```bash
   php artisan innes:create-admin
   ```

   Or set `INITIAL_ADMIN_EMAIL` and `INITIAL_ADMIN_PASSWORD` in `.env`, run the command below, then **delete those values from `.env`**:

   ```bash
   php artisan innes:create-admin --from-env
   ```

   Staff can change their password under *My password*. Super Admins can reset other staff passwords in *Staff & roles*.
6. **Proxy:** if the site sits behind a load balancer or reverse proxy, set `TRUSTED_PROXIES`.
7. **Scheduled tasks:** none are required at the moment. Notifications are sent synchronously and their outcome is recorded in `notification_deliveries`.
8. **Content:** replace every demo item (see [docs/DEMO_CONTENT.md](docs/DEMO_CONTENT.md)), set `SHOW_DEMO_NOTICE=false`, and enter verified details in *Admin → Settings*.

### Backups and recovery

- Back up the MySQL database daily, e.g. `mysqldump --single-transaction innes > innes-$(date +%F).sql.gz`, and keep copies off-server.
- Back up `storage/app/public` (uploaded images) and the production `.env` (stored securely, separately from the code).
- To restore: deploy the code, restore `.env`, import the SQL dump, restore `storage/app/public`, then run `php artisan storage:link` and `php artisan config:cache`.
- Test a restore periodically, and agree a data-retention period with the business.

---

## Decisions still needed from the business

- Verified branch addresses, phones, emails, opening hours and services per branch
- Approved service descriptions, products and brands, prices (optional) and article content, plus clinical review
- Booking rules: slot length, capacity per slot, minimum notice, booking window, cancellation cut-off
- Whether customer accounts are wanted (`FEATURE_CUSTOMER_ACCOUNTS`)
- Notification wording, staff alert address, SMTP provider; whether SMS is wanted later (the `NotificationService` is ready for another channel)
- Logo and brand assets, licensed photography, company history, mission, vision and values, practitioner profiles
- Privacy notice and consent wording, which need review against Ghana's Data Protection Act, 2012 (Act 843); data-retention period

This system deliberately does **not** include e-commerce checkout, payments, medical records, live chat or SMS.
