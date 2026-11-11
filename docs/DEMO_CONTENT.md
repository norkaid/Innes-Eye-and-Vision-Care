# Demo content to replace before production

Everything below is created by `database/seeders/DemoContentSeeder.php` for local development and review only. It is **not verified business information**. The seeder refuses to run when `APP_ENV=production`.

| Area | Demo content | Replace with | Where |
|---|---|---|---|
| Branches | "Accra Branch (Demo)", "Kumasi Branch (Demo)", address "DEMO ADDRESS…", phone `+233 00 000 0000`, hours Mon–Fri 08:30–17:00, Sat 08:30–13:00, Sun closed | Verified branch names, addresses, phones, emails, map links, opening hours | Admin → Branches |
| Branch ↔ service links | All demo services at Accra, the first four at Kumasi | Confirmed services per branch | Admin → Branches / Services |
| Services | Comprehensive Eye Examination, Vision Assessment, Glaucoma Screening, Contact Lens Fitting, Geriatric Eye Care, Children's Eye Test (+ draft "Low Vision Support") with sample descriptions | Only services the centre confirms, with approved wording and preparation info | Admin → Services |
| Product categories | Optical Frames, Sunglasses, Contact Lenses, Lenses | Confirmed categories | Admin → Product categories |
| Products | 13 items named "… (Demo)" with "Demo Brand A–F", no prices, availability labels | Real catalogue items, brands, approved photos; prices only if approved | Admin → Products |
| Articles | 4 published + 1 draft article, author "Demo Author" | Clinically reviewed, approved articles and author credits | Admin → Health articles |
| Images | SVG placeholder illustrations in `public/images/` (hero, frame, service, article) | Licensed or business-supplied photography | Uploads in admin; hero image in `resources/views/public/home.blade.php` |
| Home "Why choose us" | Three neutral statements about the website's own features | Business-approved claims only | `resources/views/public/home.blade.php` |
| About / mission / vision / values | Empty, with "to be confirmed" notices | Verified company background | Admin → Settings |
| Contact details | None set | Official phone, email, social links, response-time note | Admin → Settings |
| Privacy notice | Draft wording, retention period "[to be confirmed]" | Reviewed notice and consent wording | `resources/views/public/privacy.blade.php` |
| Demo accounts | `superadmin@`, `admin@`, `reception@`, `content@`, `customer@innes.test` | Real staff accounts via `php artisan innes:create-admin` and Admin → Staff | — |
| Sample requests | 4 appointment requests and 2 enquiries using example.com / innes.test addresses | — (not seeded in production) | — |
| Site banner | "Demo website" notice | Set `SHOW_DEMO_NOTICE=false` once all content is verified | `.env` |

A fresh production database contains **no** demo content: `php artisan migrate --force && php artisan db:seed --force` creates roles only.
