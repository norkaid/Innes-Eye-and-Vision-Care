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
6. [SMS and email notifications](#sms-and-email-notifications)
7. [Images and the media library](#images-and-the-media-library)
8. [Viewing the database](#viewing-the-database)
9. [Moving your content to the live site](#moving-your-content-to-the-live-site)
10. [Troubleshooting](#troubleshooting)
11. [Tests](#tests)
12. [Deployment](#deployment)
13. [Backups and recovery](#backups-and-recovery)
14. [Decisions still needed from the business](#decisions-still-needed-from-the-business)

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
| Book appointment `/appointments/book` | Shows only genuinely free times (a confirmed time is never offered again); customer chooses SMS and/or email updates; pays a **30% deposit of the GH₵ 500 consultation fee** (GH₵ 150) on Paystack; every request is stored as **pending** with a reference such as `IEV-7KQ2MX9P` |
| Eye health `/health-resources` | Articles with categories, author, publication date and a "not a substitute for an examination" note |
| Contact `/contact` | Contact details, branch list, enquiry form with spam protection |
| Privacy `/privacy` | Draft privacy notice for business and legal review |

Also: `sitemap.xml`, `robots.txt`, page titles, meta descriptions, canonical URLs and Open Graph tags. Pages are responsive and keyboard-accessible.

### Appointment requests

- Available times are calculated from branch opening hours, minimum notice, the booking window and slot length. **Once a time is confirmed for someone it is never offered to new requests.**
- **Deposit:** customers pay a share of the consultation fee online when they book (standard: **30% of GH₵ 500 = GH₵ 150**; the balance is paid at the clinic). The fee and percentage are set in Admin → Settings, and a service can have its own fee. Staff cannot confirm a request until the deposit is paid (or recorded as paid at the branch).
- **Notifications:** customers choose **SMS and/or email**. They are told when the deposit is received and when their appointment is confirmed (with date, time, branch and balance), a new time is proposed, or it is declined or cancelled.
- Every submission starts as **pending**. The confirmation page states clearly that the appointment is **not yet confirmed**.
- An email acknowledgement is only mentioned if it was actually sent.
- Staff (Receptionist, Administrator, Super Admin) confirm, propose a new time, decline, cancel, complete or mark a no-show. Confirmations are checked against opening hours and capacity inside a database transaction, so a slot cannot be double-booked. Every change is recorded in the request's history.

### Online shop and appointment payments

Every published product **with a price** can be added to the cart and paid for with Paystack (card or mobile money, in GH₵); products without a price show *Enquire* instead. Appointment requests include the online deposit. See [Online shop and payments](#online-shop-and-payments-paystack).

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
git clone https://github.com/norkaid/Innes-Eye-and-Vision-Care.git
```

This downloads the `main` branch, which always has the latest working version.

```bash
cd Innes-Eye-and-Vision-Care
```

On macOS/Linux use a folder such as `~/dev` instead of `C:\dev`.

> **Already cloned from the `claude/affectionate-pascal-u5lahv` branch?** Switch to `main` once with `git checkout main`, then `git pull` and `php artisan migrate`. Your database and `.env` are not affected.

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

- **Products:** Admin → Products → edit → enter a **Price**. *Sell online* is ticked by default, so the product can be bought straight away; untick it only for items customers must ask about. Optionally set **Stock available online** (it goes down with each paid order; at 0 the product can no longer be bought).
- **Delivery and pickup:** Admin → Settings → **Shop & online payments** (pickup on/off, delivery on/off, delivery fee, delivery note).
- **Appointments:** Admin → Settings → *Appointment rules* → **Standard consultation fee** (500) and **Deposit paid online** (30%). A service can override the fee (Admin → Services → *Consultation fee for this service*). After submitting the booking form the customer goes straight to Paystack to pay the deposit; if they leave, the link in their email and the account page let them pay later. Paying does **not** confirm the appointment; staff confirm it, which sends the SMS/email. If a customer pays at the branch, use **Record deposit paid at branch** on the request.

### 3. Test a payment (test keys, no real money)

Add a product to the cart, check out and on the Paystack page use Paystack's test card **4084 0840 8408 4081**, any future expiry date, CVV **408**, PIN **0000** and OTP **123456** if asked. Other test cards and test mobile-money numbers are listed in Paystack's documentation under *Test Payments*. After paying you return to the order page, which shows **Payment received**, and the order appears in **Admin → Orders**.

### 4. On the live site

- In the Paystack dashboard set the **Webhook URL** to `https://your-domain/paystack/webhook`. Paystack then confirms payments even if a customer closes the browser before returning. (Webhooks cannot reach `localhost`, which is fine: on your PC the return from Paystack confirms the payment.)
- When the business is ready to take real money, replace the test keys with the **live** keys (`pk_live_…`, `sk_live_…`) in the server's `.env` and run `php artisan config:cache`.

### 5. Notifications

Customers are told about their deposit and appointment by SMS and/or email. See [SMS and email notifications](#sms-and-email-notifications) to set them up.

### How orders work

| Status | Meaning |
|---|---|
| Awaiting payment | Order saved, not paid yet (the customer can retry from the order page) |
| Paid – to be prepared | Payment verified with Paystack; stock has been reduced |
| Being prepared → Ready for pickup / Out for delivery → Completed | Set by staff in Admin → Orders |
| Cancelled | Set by staff; paid items go back into stock. Refund the customer in the Paystack dashboard, then click **Record refund** |

Prescription lenses and contact lenses need a valid prescription: the checkout tells customers the team will check it before the order is prepared.

---

## SMS and email notifications

Customers choose on the booking form whether they want updates by **SMS**, **email** or both. They receive a message when:

- their request is received (when no deposit is due),
- their deposit is received,
- their appointment is **confirmed** (date, time, branch and the balance to pay at the clinic),
- a new time is proposed, or the request is declined or cancelled.

Texts are short and never contain medical details. Staff choose whether to notify the customer each time they change a request (the *Notify the customer* box, ticked by default).

> **On a PC nothing is delivered by default.** With `SMS_DRIVER=log` and `MAIL_MAILER=log`, messages are only written to `storage/logs/laravel.log`. Follow the steps below to reach real phones and inboxes.

### Step 1: Open an account with an SMS provider

The site works with these providers. **Arkesel** or **Hubtel** are the usual choice in Ghana.

| Provider | Website | What you need from it |
|---|---|---|
| Arkesel | [arkesel.com](https://arkesel.com) | API key |
| Hubtel | [hubtel.com](https://hubtel.com) | Client ID and client secret |
| Twilio (international) | [twilio.com](https://www.twilio.com) | Account SID, auth token and a sending number |

1. Create an account and buy some SMS credit (each text uses credit).
2. Copy the key(s) listed above from the provider's dashboard (usually under *API*, *Developers* or *Settings*).

### Step 2: Register a sender ID

The sender ID is the name customers see instead of a phone number, e.g. `InnesEye`.

- Maximum **11 characters**, letters and numbers only, no spaces.
- Request it in the provider's dashboard. In Ghana, approval can take one or more working days, and **texts may not be delivered until it is approved**.

### Step 3: Add the settings to `.env`

Open `.env` and set the lines for your provider. Never commit `.env` or paste the keys anywhere public.

**Arkesel:**

```
SMS_DRIVER=arkesel
SMS_SENDER_ID=InnesEye
ARKESEL_API_KEY=paste-your-api-key-here
```

**Hubtel:**

```
SMS_DRIVER=hubtel
SMS_SENDER_ID=InnesEye
HUBTEL_CLIENT_ID=paste-your-client-id
HUBTEL_CLIENT_SECRET=paste-your-client-secret
```

**Twilio:**

```
SMS_DRIVER=twilio
TWILIO_SID=paste-your-account-sid
TWILIO_TOKEN=paste-your-auth-token
TWILIO_FROM=+15551234567
```

Phone numbers are converted automatically: customers can type `024 123 4567`, `+233 24 123 4567` or `0233…`. Numbers starting with `0` are treated as Ghanaian (`SMS_COUNTRY_CODE=233`).

### Step 4: Set up email

Emails need a real mailbox. Replace the `MAIL_` lines in `.env`.

**Hostinger mailbox** (create it in hPanel → *Emails*):

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@your-domain.com
MAIL_PASSWORD=the-mailbox-password
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="Innes Eye & Visioncare Centre"
```

**Gmail** (for testing on a PC): turn on 2-Step Verification on the Google account, create an **app password** (Google Account → Security → App passwords), then:

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=the-16-character-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="Innes Eye & Visioncare Centre"
```

### Step 5: Apply the changes

```bash
php artisan config:clear
```

Then restart the site (`Ctrl+C`, then `php artisan serve`). On the live server run `php artisan config:cache` instead.

### Step 6: Switch customer messages on

Sign in as **Super Admin** → **Settings** → *Appointment rules & notifications*:

- tick **Send customer acknowledgement and status emails**,
- tick **Send customers SMS updates**,
- click **Save rules**.

The line under the form shows the current email and SMS method; it should no longer say `log`.

### Step 7: Send a test

Still in **Settings**, go to **Check customer notifications**, enter **your own real** email address and phone number and click **Send test**. (Demo accounts such as `superadmin@innes.test` are not real mailboxes and can never receive email.) The message at the top of the page tells you exactly what happened:

| Result | Meaning |
|---|---|
| "Test email sent" / "Test SMS sent" | It works. Check your inbox (and spam folder) and your phone |
| "…written to storage/logs/laravel.log only" | `MAIL_MAILER` or `SMS_DRIVER` is still `log`. Repeat steps 3–5 |
| "SMS is switched off" | `SMS_DRIVER` is empty or misspelled |
| "Arkesel: …" / "Hubtel: …" / "Twilio: …" | The provider refused the message: usually a wrong key, no credit, or a sender ID that is not approved yet |
| "Email failed: …" | Wrong SMTP host, port, username or password |

Finally, book a test appointment with your own phone number, pay the deposit with a Paystack test card, and confirm it in the admin: you should receive the confirmation text and email.

### Checking what was sent

Every customer message is recorded with its result (`sent`, `logged`, `skipped` or `failed`, plus the reason) in the `notification_deliveries` table. View it in **Admin → Database → Notification Deliveries**.

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
| No cart / "Add to cart" button, price says "(confirm in branch)" | Open the product in **Admin → Products → Edit**: the *Online shop* box lists exactly what is missing. Usually `PAYSTACK_SECRET_KEY` is not in `.env` (add it, run `php artisan config:clear`, restart the site) |
| "The payment provider refused the request" | Wrong or mismatched Paystack keys (test and live keys mixed), or the currency is not enabled on the Paystack account |
| Paid on Paystack but order still "Awaiting payment" | The customer closed the page before returning. Set the webhook URL (live site), or click **Check again** in Admin → Payments |
| Customers don't receive SMS or email | Follow [SMS and email notifications](#sms-and-email-notifications) and use **Send test** in Admin → Settings: it shows the exact problem (still `log`, wrong key, no credit or sender ID not approved) |
| "The deposit for this request has not been paid yet" when confirming | The customer has not finished paying. Send them the payment link shown on the request, or use **Record deposit paid at branch** |

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

The suite (147 tests) covers: public pages and draft visibility, search and filters, booking validation (past dates, closed days, lead time, slot grid, branch/service combinations, spam, rate limits), pending status, status transitions and history, capacity conflicts, the role matrix, guest and customer exclusion from admin, customer ownership, cut-off rules, CRUD for services, products, branches, articles and team profiles, upload validation and image processing, media library, website images, content transfer (including tampered files), the database viewer, the shop and Paystack payments (cart, checkout, server-side verification, tampered amounts, abandoned payments, stock, signed order links, webhook signatures, appointment deposits, order management and refunds), SMS and email notifications by customer choice, confirmed times never offered again, staff privilege escalation, CSRF, output escaping, no exposure of sensitive data and no committed secrets. GitHub Actions runs the suite on MySQL 8 for every push.

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
- Shop: prices and stock, pickup/delivery options and fee, returns and refund policy, and the prescription-check process; consultation fees per service and the deposit refund policy for declined or cancelled appointments; when to switch Paystack to live keys
- SMS provider (Arkesel, Hubtel or other) and approved sender ID
- Notification wording, staff alert address, SMTP provider; whether SMS is wanted later (`NotificationService` is ready for another channel)
- Logo and brand assets, licensed photography, company history, mission, vision and values, and team profiles (with each person's consent)
- Privacy notice and consent wording, which need review against Ghana's Data Protection Act, 2012 (Act 843); data-retention period
