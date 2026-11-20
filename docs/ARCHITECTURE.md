# Architecture and design decisions

## Overview

A conventional server-rendered Laravel application:

```
Browser ──► routes/web.php ──► middleware (auth, staff, customer, can:<permission>, throttle)
                               └► Controllers (Public / Auth / Customer / Admin)
                                    ├► validation (FormRequests / $request->validate)
                                    ├► Services (business rules) ──► Eloquent models ──► MySQL
                                    └► Blade views + components (Tailwind, Alpine)
```

- **`app/Services/AvailabilityService`** calculates bookable slots from branch hours plus the business settings (lead time, window, slot length, capacity) and existing *confirmed* appointments. The same code drives the booking form's time buttons (JSON endpoint), server-side validation and staff confirmation checks, so the UI never offers a time the server would reject.
- **`AppointmentService`** handles every status change: the allowed transitions defined in `App\Enums\AppointmentStatus`, a DB transaction with `lockForUpdate` on the branch row (so concurrent confirmations are serialised before the capacity check), history rows, audit log and notifications.
- **`EnquiryService`**, **`NotificationService`** (single place to add SMS later; records sent/failed/skipped and never claims a send that didn't happen), **`Settings`** (typed key/value settings with defaults in `config/innes.php`), **`AuditLogger`** (strips sensitive keys).
- **`ImageUploader`** + **`ImageOptimizer`**: every uploaded picture is validated (type, size, pixel size read from the file path rather than `realpath()`, which fails on some Windows set-ups), turned upright using EXIF, shrunk to 2000 px and re-compressed, then stored **in the database** (`media` for details, `media_contents` for the bytes, kept apart so lists never load image data). `MediaFileController` serves them at `/media/<folder>/<random>.<ext>` with immutable caching. Keeping images in the database means one backup holds everything and no writable public folder is needed. A picture in use (product, service, branch, article, team profile, logo, website images) cannot be deleted.
- **`ContentTransfer`**: exports website content (settings, media, branches with hours and services, services, categories, products, articles, team profiles) to one gzip JSON file and imports it on another installation in a single transaction, matching items by slug and never deleting. Imported pictures must decode as real JPEG/PNG/WebP and image paths are restricted to the media library. Accounts, appointments, enquiries and logs are never exported.

## Authorisation

- Roles live in `roles` / `role_user`. Each role's permissions are defined in `config/innes.php` and reviewed in code, not editable in the UI, which keeps privilege changes auditable through version control. One Gate is registered per permission.
- Admin routes require `auth` + `staff` (an active staff account with `dashboard.view`), plus `can:<permission>` per module. Destructive actions use POST/PATCH/DELETE only.
- Staff can only grant roles whose permissions they already hold, cannot change their own roles, cannot deactivate themselves, and the last active Super Admin cannot be removed.
- Customer access to appointments goes through `AppointmentPolicy` (ownership by `user_id`, never by email); non-owners receive 404.
- Sensitive model fields (`status`, `staff_note`, `handled_by`, `account_type`, `is_active`, …) are not mass-assignable.

## Data model

```
users ─< role_user >─ roles
users ─< appointments >─ branches ─< branch_opening_hours
                     >─ services >─< branches  (branch_service)
appointments ─< appointment_status_histories
product_categories ─< products ─< enquiries ─< enquiry_notes
article_categories ─< health_articles >─ users (author)
media ─ media_contents (1:1, image bytes)
team_members
business_settings, audit_logs, notification_deliveries

Picture columns (products.image_path, services.image_path, branches.image_path,
health_articles.featured_image_path, team_members.image_path, and the logo_path /
hero_image_path / about_image_path settings) hold a media path such as
media/products/<random>.jpg.
```

Foreign keys use `restrictOnDelete` where history must be kept (an appointment's branch and service), and `nullOnDelete` / `cascadeOnDelete` elsewhere. Services, products, branches, articles and users use soft deletes ("archive"). Indexes cover status, dates, reference codes, slugs and filter columns. Status columns are strings validated by PHP enums, which keeps migrations portable.

## Local versus live database

The code runs on SQLite (one file, used on a PC: nothing to install) and MySQL (the live server). Migrations avoid database-specific features except one: on MySQL the image column is widened to `MEDIUMBLOB`. Tests run on both. The local database file is never committed, because it can contain customer details; **Content transfer** moves the website content between the two.

## Privacy choices

- The booking form collects only contact details, branch, service, preferred time and an optional 500-character note, with a warning not to enter medical details.
- Emails contain minimal information; staff alerts include only a reference and a link.
- Reference codes use 8 random characters from a 31-character alphabet. The confirmation page reads the reference from the session only, never from the URL.
- Report exports contain aggregates only. The audit log never stores passwords, notes or messages.
- The **Database** page (Super Admin only) is read-only, hides password hashes, tokens and session tables, and records each table viewed in the activity log.
- Security headers are set and authenticated pages are sent with `Cache-Control: no-store` (public images excepted). HTTPS is forced in production, sessions are encrypted, and login, booking, contact, registration and password reset are rate-limited. Public forms also have a honeypot and a minimum fill time.

## Why no Livewire / extra packages

The interactive pieces (availability loading, mobile menus, confirm dialogs) are small, and Alpine.js covers them. Avoiding Livewire and a permissions package keeps the dependency surface and maintenance burden low for a small clinic.
