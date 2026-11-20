# Innes Eye & Visioncare Centre – Website & Management Platform

A web platform for **Innes Eye & Visioncare Centre, Ghana**. It combines a public website, online appointment requests and enquiries, an optional customer account area, and a secure staff dashboard with role-based access.

> **Status:** working release, ready for the centre's real content. Items seeded on a local PC are sample data marked **DEMO DATA — REPLACE BEFORE PRODUCTION**; the live server never receives demo data. See [docs/DEMO_CONTENT.md](docs/DEMO_CONTENT.md).

| Document | What it covers |
|---|---|
| [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md) | Putting the site live on Hostinger, step by step |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | How the code is organised, database relationships, design decisions |
| [docs/DEMO_CONTENT.md](docs/DEMO_CONTENT.md) | Every piece of sample content that must be replaced |
| [docs/PROJECT_BRIEF.md](docs/PROJECT_BRIEF.md), [docs/DEVELOPMENT_BRIEF.md](docs/DEVELOPMENT_BRIEF.md) | The original briefs |

---

## Contents

1. [Features](#features)
2. [Roles](#roles)
3. [Set up the system on your PC](#set-up-the-system-on-your-pc)
4. [Everyday use on your PC](#everyday-use-on-your-pc)
5. [Online shop and payments (Paystack)](#online-shop-and-payments-paystack)
6. [Images and the media library](#images-and-the-media-library)
7. [Viewing the database](#viewing-the-database)
8. [Moving your content to the live site](#moving-your-content-to-the-live-site)
9. [Troubleshooting](#troubleshooting)
10. [Tests](#tests)
11. [Deployment](#deployment)
12. [Backups and recovery](#backups-and-recovery)
13. [Decisions still needed from the business](#decisions-still-needed-from-the-business)

---

## Features

### Public website

| Page | Highlights |
|---|---|
| Home `/` | Main picture (chosen in Settings), introduction, featured services and products, why choose us, branches, latest articles, booking call-to-action |
| About `/about` | Company background, mission, vision and values, team profiles, clinic picture |
| Services `/services` | Search and branch filter; detail pages with preparation notes and the branches that offer each service |
| Frames & lenses `/products` | Category filter, keyword search, pagination, product detail pages with **Add to cart** (products sold online) and **Enquire about this product** |
| Cart and checkout `/cart` | Pickup at a branch or delivery, payment by card or mobile money on Paystack's secure page, order page and emailed receipt |
| Branches `/branches` | Photo, address, phone, opening hours, directions link and services per branch |
| Book appointment `/appointments/book` | Shows only genuinely free times; every request is stored as **pending** with a reference such as `IEV-7KQ2MX9P` |
| Eye health `/health-resources` | Articles with categories, author, publication date and a "not a substitute for an examination" note |
| Contact `/contact` | Contact details, branch list, enquiry form with spam protection |
| Privacy `/privacy` | Draft privacy notice for business and legal review |

Also: `sitemap.xml`, `robots.txt`, page titles, meta descriptions, canonical URLs and Open Graph tags. Pages are responsive and keyboard-accessible.

### Appointment requests

- Available times are calculated from branch opening hours, minimum notice, the booking window, slot length, capacity per slot and existing *confirmed* appointments.
- Every submission starts as **pending**. The confirmation page states clearly that the appointment is **not yet confirmed**.
- An email acknowledgement is only mentioned if it was actually sent.
- Staff (Receptionist, Administrator, Super Admin) confirm, propose a new time, decline, cancel, complete or mark a no-show. Confirmations are checked against opening hours and capacity inside a database transaction, so a slot cannot be double-booked. Every change is recorded in the request's history.

### Online shop and appointment payments

Products marked **Sell online** can be added to a cart and paid for with Paystack (card or mobile money, in GH₵); services can have a **booking fee** that customers pay online after requesting an appointment. See [Online shop and payments](#online-shop-and-payments-paystack).

### Customer accounts (optional, `FEATURE_CUSTOMER_ACCOUNTS`)

Register, sign in, reset password, edit profile, see own appointment requests (cancel, reschedule, pay the booking fee), own orders and own enquiries. Ownership is checked on the server.

### Admin dashboard `/admin`

| Module | What it does |
|---|---|
| Dashboard | Live counts from the database, oldest requests waiting, today's confirmed appointments, recent enquiries and activity, filtered by role |
| Appointments | Search and filter by status, branch, service and date; confirm, propose a new time, decline, cancel, complete, no-show; private notes; full status history |
| Enquiries | Filter, assign, set status (new / in progress / resolved / spam), private notes |
| Orders | Shop orders with items, customer, pickup/delivery and payment attempts; move through *Paid → Being prepared → Ready for pickup / Out for delivery → Completed*, or cancel (items go back into stock); record refunds |
| Payments | Every Paystack transaction (orders and booking fees) with result, method and totals for a date range; re-check a payment with Paystack |
| Customers | Contact details and appointment history; edit; deactivate |
| Services, Products, Product categories, Branches, Health articles | Create, edit, publish/unpublish, archive and restore; pictures; branch↔service links; opening hours; article draft → preview → publish; product price, **Sell online** and optional stock; service **booking fee** |
| Team profiles | Practitioner and staff profiles (photo, role, qualifications, short bio) shown on the About page when published |
| Media library | Upload the logo, glasses photos and other pictures (up to 20 at once) into folders; alt text; copy links; pictures that are in use cannot be deleted |
| Staff & roles | Create and deactivate staff, assign roles; staff cannot grant more than they have |
| Settings | Logo; home and About pictures; business details and About text; shop options (pickup, delivery, delivery fee); appointment rules and notifications (Super Admin only) |
| Reports | Date-filtered totals; CSV export contains counts only, no personal data |
| Activity log | Audit trail of administrative actions |
| Database | Super Admin only: read-only view of every table. Password hashes, tokens and session data are never shown |
| Content transfer | Super Admin only: download all website content as one file and import it on another copy of the site (e.g. PC → live server) |

The system deliberately does **not** include medical records, live chat or SMS.

---

## Roles

| Role | Can use |
|---|---|
| Super Admin | Everything, including staff management, booking rules, Database and Content transfer |
| Administrator | Appointments, enquiries, customers, orders, payments, all website content, team profiles, media, business details, shop settings and images, reports, activity log |
| Receptionist | Appointments, enquiries, orders and the customer details needed for them (not the Payments list) |
| Content Manager | Services, products, categories, articles, team profiles and media (no appointment or customer data) |
| Customer | Own profile, own requests and enquiries |
| Guest | Public pages and public forms |

Permissions are defined in `config/innes.php` and checked with Gates on every route and action.

---

## Set up the system on your PC

These steps use **SQLite**: the whole database is one file (`database\database.sqlite`), so you don't need to install MySQL. The live server uses MySQL; the code works with both.

### What you need

| Tool | Why | Where to get it |
|---|---|---|
| **PHP 8.3 or newer** and **Composer** | Runs the website | Windows/macOS: [Laravel Herd](https://herd.laravel.com) (free, installs both). Linux: your package manager |
| **Git** | Downloads the code and updates | [git-scm.com](https://git-scm.com) |
| **VS Code** (optional) | Editing files, terminal | [code.visualstudio.com](https://code.visualstudio.com) |
| **DB Browser for SQLite** (optional) | Looking at the database | [sqlitebrowser.org](https://sqlitebrowser.org/dl/) |

You do **not** need Node.js: the finished CSS and JavaScript are already in the repository (`public/build`). Node.js 20+ is only needed if you change the design files in `resources/css` or `resources/js`.

> **Windows:** run the commands below in **PowerShell** or the VS Code terminal, **one line at a time**. Windows PowerShell does not accept `&&`.
>
> **Keep the project out of OneDrive.** OneDrive locks files while it syncs, which breaks Laravel. Use a folder such as `C:\dev`.

### Step 1: Check PHP and Composer

Close and reopen the terminal after installing Herd, then run:

```bash
php -v
```

```bash
composer -V
```

`php -v` must show version 8.3 or newer.

### Step 2: Set PHP's upload limits (once)

PHP's standard settings only accept uploads up to 2 MB, which blocks most phone photos.

- **With Herd:** open Herd → **Settings → PHP** → set **Max file upload size** to `40` (MB).
- **Without Herd:** find your settings file with `php --ini` (the "Loaded Configuration File" line), open it and set:

  ```
  upload_max_filesize = 40M
  post_max_size = 64M
  ```

  Remove the `;` in front of these lines if present: `extension=fileinfo`, `extension=gd`, `extension=mbstring`, `extension=openssl`, `extension=intl`, `extension=pdo_sqlite`, `extension=sqlite3`, `extension=zip`.

Check the result (it must not say `2M / 8M`):

```bash
php -r "echo ini_get('upload_max_filesize'), ' / ', ini_get('post_max_size');"
```

### Step 3: Download the code

```bash
cd C:\dev
```

```bash
git clone -b claude/affectionate-pascal-u5lahv https://github.com/norkaid/Innes-Eye-and-Vision-Care.git
```

```bash
cd Innes-Eye-and-Vision-Care
```

On macOS/Linux use a folder such as `~/dev` instead of `C:\dev`.

### Step 4: Install the PHP packages

```bash
composer install
```

### Step 5: Create the settings file

Windows (PowerShell):

```bash
Copy-Item .env.example .env
```

macOS/Linux:

```bash
cp .env.example .env
```

Then:

```bash
php artisan key:generate
```

The default `.env` is ready for local use: it uses SQLite (`DB_CONNECTION=sqlite`) and has **no** `DB_DATABASE` line. Do not add one, or Laravel will look for a different database file.

### Step 6: Create the database and load demo content

```bash
php artisan migrate --seed
```

If it asks *"Would you like to create it?"* about `database.sqlite`, answer **yes**. This creates all tables and fills them with clearly marked demo content and demo accounts.

Check that the site uses the right file:

```bash
php artisan db:show
```

The `Database` line must end in `database\database.sqlite` (or `database/database.sqlite`).

### Step 7: Start the site

```bash
php artisan serve
```

Open **http://localhost:8000**. The admin dashboard is at **http://localhost:8000/admin**. Press `Ctrl+C` in the terminal to stop the site.

### Demo accounts (PC only)

Demo content and accounts are only created when `APP_ENV` is not `production`. Every account uses the password **`Demo-Password-2026`** (the value of `DEMO_USER_PASSWORD` in `.env`).

| Email | Role |
|---|---|
| superadmin@innes.test | Super Admin |
| admin@innes.test | Administrator |
| reception@innes.test | Receptionist |
| content@innes.test | Content Manager |
| customer@innes.test | Customer |

Emails are not sent on your PC: they are written to `storage/logs/laravel.log` while `MAIL_MAILER=log`.

### Using MySQL on your PC instead (optional)

If you prefer MySQL (for example to use MySQL Workbench):

1. Create a database: `CREATE DATABASE innes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Make sure `extension=pdo_mysql` is enabled in `php.ini`.
3. In `.env`, replace `DB_CONNECTION=sqlite` with the MySQL lines (remove the `#` in front of them) and fill in your username and password.
4. Run `php artisan config:clear`, then `php artisan migrate --seed`.

Content entered in SQLite does not move to MySQL automatically; use [Content transfer](#moving-your-content-to-the-live-site) to copy it.

### Turning on payments on your PC

Add your Paystack **test** keys to `.env` (see [Online shop and payments](#online-shop-and-payments-paystack)), then run `php artisan config:clear` and restart `php artisan serve`.

---

## Everyday use on your PC

| Task | Command |
|---|---|
| Start the site | `php artisan serve` |
| Stop the site | `Ctrl+C` in the terminal |
| Get the latest code | `git pull`, then `php artisan migrate` (adds new tables, keeps your data) |
| After changing `.env` | `php artisan config:clear` |

### Which commands are safe for your data?

Your content lives in `database\database.sqlite`. That file is **not** in Git: `git pull` never overwrites it and `git push` never uploads it (it can contain customer details).

| Command | Effect on your content |
|---|---|
| `php artisan serve` | Safe: only starts the site |
| `git pull` | Safe: only updates code |
| `php artisan migrate` | Safe: only adds new tables or columns |
| `php artisan config:clear` | Safe: only clears saved settings |
| `php artisan migrate:fresh --seed` | **Erases everything** and reloads demo content. Do not run once you have entered real information |

Back up your work by copying `database\database.sqlite` somewhere safe (stop the site and close DB Browser first).

---

## Online shop and payments (Paystack)

Customers pay on **Paystack's own secure page** (card or mobile money, in GH₵). Card details never reach this website. The website only records the result after **checking it with Paystack on the server**.

### 1. Add your Paystack keys

In the Paystack dashboard go to **Settings → API Keys & Webhooks** and copy the keys into `.env`:

```
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...
```

Then run `php artisan config:clear` and restart the site. Until `PAYSTACK_SECRET_KEY` is set, the cart and payment buttons stay hidden and **Admin → Payments** shows a warning. **Never commit the secret key or paste it anywhere public.** `FEATURE_SHOP=false` or `FEATURE_APPOINTMENT_PAYMENTS=false` switch either part off.

### 2. Choose what can be paid for

- **Products:** Admin → Products → edit → enter a **Price**, tick **Sell online**, and optionally set **Stock available online** (it goes down with each paid order; at 0 the product can no longer be bought).
- **Delivery and pickup:** Admin → Settings → **Shop & online payments** (pickup on/off, delivery on/off, delivery fee, delivery note).
- **Appointments:** Admin → Services → edit → **Booking fee**. Customers who request that service see **Pay booking fee** on the confirmation page, in their email and in their account. Paying does **not** confirm the appointment; staff still confirm it.

### 3. Test a payment (test keys, no real money)

Add a product to the cart, check out and on the Paystack page use Paystack's test card **4084 0840 8408 4081**, any future expiry date, CVV **408**, PIN **0000** and OTP **123456** if asked. Other test cards and test mobile-money numbers are listed in Paystack's documentation under *Test Payments*. After paying you return to the order page, which shows **Payment received**, and the order appears in **Admin → Orders**.

### 4. On the live site

- In the Paystack dashboard set the **Webhook URL** to `https://your-domain/paystack/webhook`. Paystack then confirms payments even if a customer closes the browser before returning. (Webhooks cannot reach `localhost`, which is fine: on your PC the return from Paystack confirms the payment.)
- When the business is ready to take real money, replace the test keys with the **live** keys (`pk_live_…`, `sk_live_…`) in the server's `.env` and run `php artisan config:cache`.

### How orders work

| Status | Meaning |
|---|---|
| Awaiting payment | Order saved, not paid yet (the customer can retry from the order page) |
| Paid – to be prepared | Payment verified with Paystack; stock has been reduced |
| Being prepared → Ready for pickup / Out for delivery → Completed | Set by staff in Admin → Orders |
| Cancelled | Set by staff; paid items go back into stock. Refund the customer in the Paystack dashboard, then click **Record refund** |

Prescription lenses and contact lenses need a valid prescription: the checkout tells customers the team will check it before the order is prepared.

---

## Images and the media library

- Upload pictures in **Admin → Media library** and sort them into folders: *Logo & branding, Glasses & products, Services, Branches & premises, Team & practitioners, Articles, General*. Add a short description (alt text) for screen readers.
- Product, service, branch, article and team forms can upload a new picture or **choose one from the media library**.
- **Admin → Settings** has the **Logo** and **Website images** (home page main picture, About page picture).
- Photos up to **10 MB** (JPG, PNG or WebP) are accepted. They are turned the right way up, shrunk to 2000 px on the longest side and compressed before saving. Logos keep their transparent background. iPhone HEIC photos must be saved as JPG first.
- Pictures are **stored in the database** (tables `media` and `media_contents`) and served at `/media/<folder>/<random>.<ext>`. A database backup therefore includes every picture.
- If uploads fail, **Admin → Media library** shows a red box listing the PHP setting to change.

---

## Viewing the database

- **In the admin dashboard:** sign in as Super Admin → **Database** (read-only).
- **On your PC:** open `database\database.sqlite` in **DB Browser for SQLite** (→ *Browse Data*) or with the **SQLite Viewer** extension in VS Code. MySQL Workbench cannot open SQLite files.
- **On Hostinger:** hPanel → *Databases* → *phpMyAdmin*.

Close DB Browser (or click *Write Changes* / *Revert Changes*) before saving things in the admin dashboard; while it holds unsaved changes, the website cannot write to the file. Adding `DB_JOURNAL_MODE=wal` to `.env` lets DB Browser stay open for reading.

---

## Moving your content to the live site

The live site has its own, empty MySQL database: deploying copies the code, not your content. You can either type everything into the live admin, or copy it across:

1. **On your PC:** sign in as Super Admin → **Content transfer** → **Download export file** (`innes-content-<date>.json.gz`).
2. **On the live site:** Super Admin → **Content transfer** → choose the file → tick the confirmation → **Import content**.

Copied: business details, booking rules, shop settings, logo and website images, media library, branches (opening hours, services, photo), services (with booking fees), product categories, products (with price, *Sell online* and stock), article categories, health articles and team profiles. **Never copied:** staff and customer accounts, appointments, enquiries, orders, payments, activity log, and the Paystack keys (they are in `.env`). Demo items are left out unless you tick "Also include items that are still demo content".

Importing adds new items and updates items with the same web address (slug); it never deletes anything. You can keep editing directly in the live admin at any time. For very large files use the commands:

```bash
php artisan innes:export-content
```

```bash
php artisan innes:import-content storage/app/innes-content-<date>.json.gz
```

---

## Troubleshooting

| Problem | Cause and fix |
|---|---|
| `php` is not recognised | PHP is not installed or the terminal was opened before installing it. Install Herd, then close and reopen the terminal |
| `vendor/autoload.php` not found | Run `composer install` in the project folder |
| `bootstrap\cache directory must be present and writable` | The project is inside OneDrive. Clone it into `C:\dev` instead |
| `The token '&&' is not a valid statement separator` | Windows PowerShell: run each command on its own line |
| Database is empty / `db:show` shows `Database ... innes` | `.env` contains a `DB_DATABASE=` line. Delete it (or put `#` in front), run `php artisan config:clear`, then `php artisan migrate` |
| Only 26 tables / new admin pages missing | Old code. Run `git pull`, then `php artisan migrate` |
| `unable to open database file` when saving | DB Browser for SQLite has the file open with unsaved changes. Write or revert the changes, close it, and save again |
| Picture upload fails | Open **Admin → Media library**: the red box names the setting to change. Usually `upload_max_filesize` / `post_max_size` in `php.ini` (find it with `php --ini`; with Herd use Herd → Settings → PHP), then restart `php artisan serve` |
| `php -r ...` still shows `2M / 8M` | You edited a different `php.ini`. Run `php --ini` and edit the file named on "Loaded Configuration File" |
| `The upload was too large` page | Too many or too large pictures in one go. Upload fewer at a time or raise `post_max_size` |
| No cart / "Add to cart" button | `PAYSTACK_SECRET_KEY` is missing from `.env` (run `php artisan config:clear` after adding it), or the product has no price, is not ticked *Sell online*, is unpublished or out of stock |
| "The payment provider refused the request" | Wrong or mismatched Paystack keys (test and live keys mixed), or the currency is not enabled on the Paystack account |
| Paid on Paystack but order still "Awaiting payment" | The customer closed the page before returning. Set the webhook URL (live site), or click **Check again** in Admin → Payments |

---

## Tests

```bash
php artisan test
```

The tests use an in-memory SQLite database and never touch your data. To run them against MySQL, use a separate, empty test database:

```bash
DB_CONNECTION=mysql DB_DATABASE=innes_testing php artisan test
```

Code style:

```bash
vendor/bin/pint --test
```

The suite (139 tests) covers: public pages and draft visibility, search and filters, booking validation (past dates, closed days, lead time, slot grid, branch/service combinations, spam, rate limits), pending status, status transitions and history, capacity conflicts, the role matrix, guest and customer exclusion from admin, customer ownership, cut-off rules, CRUD for services, products, branches, articles and team profiles, upload validation and image processing, media library, website images, content transfer (including tampered files), the database viewer, the shop and Paystack payments (cart, checkout, server-side verification, tampered amounts, abandoned payments, stock, signed order links, webhook signatures, booking fees, order management and refunds), staff privilege escalation, CSRF, output escaping, no exposure of sensitive data and no committed secrets. GitHub Actions runs the suite on MySQL 8 for every push.

---

## Deployment

**Hostinger:** follow [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md). In short, for any PHP host:

1. **Server:** PHP 8.3+, MySQL 8, HTTPS certificate, web root pointing at `public/`, `storage/` and `bootstrap/cache/` writable.
2. **Code:** `composer install --no-dev --optimize-autoloader`. The built assets are already in `public/build`.
3. **`.env`:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, the MySQL `DB_*` lines (a user with rights only on this database), real `MAIL_*` SMTP settings and the `PAYSTACK_*` keys. Never commit `.env`. Set the Paystack webhook URL to `https://your-domain/paystack/webhook`.
4. **Database:** `php artisan key:generate`, then `php artisan migrate --force`, then `php artisan db:seed --force` (in production this creates the roles only, never demo data).
5. **First administrator** (there is no default password): `php artisan innes:create-admin`, or set `INITIAL_ADMIN_EMAIL` / `INITIAL_ADMIN_PASSWORD` in `.env`, run `php artisan innes:create-admin --from-env` and then delete those values from `.env`.
6. **Caches:** `php artisan storage:link`, then `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
7. **Content:** import your content (see above) or enter it in the live admin, then set `SHOW_DEMO_NOTICE=false` once all demo content is replaced.
8. **Updates later:** `bash deploy.sh` (maintenance mode, `git pull`, `composer install`, migrations, caches).

No scheduled tasks are needed. Notifications are sent immediately and their outcome is stored in `notification_deliveries`. Behind a load balancer or reverse proxy, set `TRUSTED_PROXIES`.

---

## Backups and recovery

- Back up the MySQL database daily, e.g. `mysqldump --single-transaction --hex-blob innes | gzip > innes-$(date +%F).sql.gz`, or export it in phpMyAdmin, and keep copies off the server. The dump includes every uploaded picture.
- Keep a private copy of the production `.env`; it contains the `APP_KEY`.
- To restore: deploy the code, restore `.env`, import the SQL dump, then run `php artisan config:cache`.
- A **Content transfer** export is a handy extra backup of the website content (not of accounts or appointments).
- Test a restore periodically and agree a data-retention period with the business.

---

## Decisions still needed from the business

- Verified branch addresses, phones, emails, opening hours and services per branch
- Approved service descriptions, products and brands, prices (optional) and article content, with clinical review
- Booking rules: slot length, capacity per slot, minimum notice, booking window, cancellation cut-off
- Whether customer accounts are wanted (`FEATURE_CUSTOMER_ACCOUNTS`)
- Shop: which products are sold online and at what price, stock levels, pickup/delivery options and fee, returns and refund policy, and the prescription-check process; booking fees per service and the refund policy for declined or cancelled appointments; when to switch Paystack to live keys
- Notification wording, staff alert address, SMTP provider; whether SMS is wanted later (`NotificationService` is ready for another channel)
- Logo and brand assets, licensed photography, company history, mission, vision and values, and team profiles (with each person's consent)
- Privacy notice and consent wording, which need review against Ghana's Data Protection Act, 2012 (Act 843); data-retention period
