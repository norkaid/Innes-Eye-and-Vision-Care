# AI Development Prompt: Innes Eye & Visioncare Centre Website and Management Platform

## 1. Your role

Act as a senior full-stack software engineer, solution architect, UI/UX designer, database designer, and QA engineer. Build a production-minded, responsive web application for **Innes Eye & Visioncare Centre, Ghana**.

Do not build only a static landing page or a visual prototype. Build a complete application with a public-facing website, customer appointment and enquiry workflows, and a secure staff administration dashboard.

Work in clear phases. First inspect the existing repository and explain its structure. If code already exists, preserve useful working functionality and conventions rather than replacing the project blindly. Before making major architectural changes, explain the reason. Implement the application in small, testable increments and keep the project runnable.

## 2. Project title and purpose

**Design and Development of a Comprehensive Web-Based Management and Customer Service Platform for Innes Eye & Visioncare Centre, Ghana.**

The platform should help visitors understand the centre's services, browse its optical catalogue, locate branches, submit appointment requests, and contact the business. Authorised staff should be able to manage appointment requests, enquiries, services, products, branches, website content, and staff access.

The main goals are to:
- Strengthen the centre's online presence.
- Make service, product, and branch information easy to find.
- Make appointment requests and enquiries convenient.
- Give staff a secure, practical dashboard for agreed administrative tasks.
- Provide a polished, accessible experience on mobile, tablet, and desktop.
- Protect customer information and avoid collecting unnecessary health information.

This is a proposed system. Do not assume that unverified directory information, services, branches, staff, opening hours, or business policies are accurate.

## 3. Required technology stack

Use the following stack unless the repository already has a sound, working stack that should be preserved:

### Backend
- **PHP 8.3 or a currently supported PHP version**
- **Laravel**, using the latest stable release compatible with the hosting environment
- Laravel Eloquent ORM and migrations
- Laravel validation, authentication, authorisation policies, CSRF protection, and secure session handling

### Frontend
- Laravel Blade templates
- **Tailwind CSS** for styling
- Alpine.js for lightweight interface interactions
- Use Livewire where it materially simplifies interactive forms, tables, filters, or dashboard workflows. Do not add unnecessary frontend frameworks.

### Database
- **MySQL 8.0+** (or a compatible supported MySQL version)
- Use relational constraints, indexes, foreign keys, and transactions where appropriate.

### Development and quality tools
- Composer and npm
- Laravel Pint or an equivalent PHP formatter
- PHPUnit or Pest for automated tests
- Git for version control
- `.env.example` for documented environment configuration

### Deployment
- Deploy to a PHP-compatible hosting environment that supports the selected Laravel version, MySQL, HTTPS, scheduled tasks if needed, and writable storage.
- Keep deployment instructions independent of any one hosting provider.

If a different stack is already established in the repository, do not migrate simply for preference. Explain the trade-offs and keep the implementation consistent.

## 4. Design direction and brand system

Create a clean, modern website for **Innes Eye & Visioncare Centre**, an optician focused on trusted eye care and stylish vision solutions. The design should feel professional, calming, medical, and welcoming.

Use:
- A bright layout with generous white space.
- Clear page headings and readable typography.
- Strong calls to action such as **Book Appointment** and **View All Frames**.
- Relevant, high-quality images of eye examinations, optical frames, friendly professionals, and confident patients.
- Clear navigation and consistent page layouts.
- Responsive layouts for desktop, tablet, and mobile.
- Accessible form labels, keyboard navigation, visible focus states, and meaningful image alt text.

Use properly licensed images or images supplied and approved by the business. Do not use random images that misrepresent the actual clinic, staff, products, or facilities.

### Colour palette

| Name | Hex | Use |
|---|---|---|
| Deep Navy | `#123047` | Headings, navigation, professional accents |
| Teal | `#168C8C` | Primary buttons, links, key highlights |
| Soft Sky Blue | `#D9F0F2` | Calm backgrounds and medical information sections |
| Warm Sand | `#F4EBDD` | Warm accent sections |
| White | `#FFFFFF` | Main background |
| Slate Gray | `#52616B` | Body text and supporting text |

Use navy to communicate trust, teal for actions, soft blue for calm sections, and warm sand sparingly. Maintain strong contrast. Never use colour alone to communicate an error, status, or other important meaning.

### Shared UI components

Create reusable components for:
- Header and responsive navigation
- Footer
- Primary, secondary, and text buttons
- Page title/intro sections
- Service cards
- Product cards
- Branch cards
- Practitioner cards (only when approved information exists)
- Appointment status labels
- Form fields and validation messages
- Confirmation and error alerts
- Pagination
- Empty states and loading states
- Admin sidebar and dashboard summary cards

## 5. Public website pages

Build the following pages. Use clean URLs and consistent layouts.

### 5.1 Home (`/`)
Include:
- Hero section with a concise value proposition and approved hero image.
- Primary CTA: **Book Appointment**.
- Secondary CTA: **View All Frames**.
- Brief introduction to the centre.
- Featured services.
- Featured optical products.
- Why choose the centre, using only claims approved by the business.
- Branch preview with links to branch details.
- Eye-health article preview.
- Contact/appointment CTA near the bottom.

Do not invent statistics, awards, accreditations, years of experience, testimonials, or clinical claims.

### 5.2 About (`/about`)
Include:
- Verified company background.
- Mission, vision, and values, if provided.
- Approved practitioner or team profiles, if provided.
- Relevant clinic images supplied or approved by the business.

Use placeholders clearly marked as content requiring business confirmation until real copy is supplied.

### 5.3 Services (`/services`)
Include:
- Searchable or filterable service listing if there are enough services to justify it.
- Service detail pages (`/services/{slug}`).
- Description, what the service involves, relevant preparation information supplied by the clinic, and branches offering the service.
- CTA to request an appointment.

Potential service categories to verify with the business include comprehensive eye examinations, vision assessments, glaucoma screening, contact lens fitting, geriatric eye care, and other specialist services. Do not publish any service as available until confirmed.

### 5.4 Optical catalogue (`/products`)
Include:
- Product listing with category filters and keyword search.
- Product detail page (`/products/{slug}`).
- Product image, name, category, description, brand, and availability/enquiry status where known.
- CTA such as **Enquire About This Product**.
- Pagination.

For the initial release, treat this as a catalogue and enquiry feature, not a full e-commerce checkout. Only add cart, payment, delivery, and order management if the business explicitly requests them.

### 5.5 Branches (`/branches`)
Include:
- Branch listing and individual branch detail pages (`/branches/{slug}`).
- Verified address, phone, email if applicable, opening hours, map/directions link, and services available at that branch.
- Do not hardcode unverified branch addresses or opening hours.

### 5.6 Appointment request (`/appointments/book`)
Build a multi-field but simple, mobile-friendly form:
- Customer name
- Email address
- Phone number
- Preferred branch
- Requested service
- Preferred date
- Preferred time or time range, if the clinic supplies bookable slots
- Optional short note with a warning not to enter sensitive medical details

Validate all inputs on the server. Prevent past dates. Apply business-configured opening days, hours, lead time, and booking rules. Do not show a time as available unless availability is actually calculated from the system's booking rules and existing appointments.

Important: the initial submission creates a **pending appointment request**, not a confirmed appointment. Display a clear message that the clinic must review and confirm the request. Send an acknowledgement only if email delivery is configured.

### 5.7 Appointment request confirmation (`/appointments/received`)
Show a safe confirmation message and reference number. Do not expose internal IDs, private customer information, or appointment details to unauthenticated users.

### 5.8 Contact (`/contact`)
Include:
- Verified contact details
- Branch contact information
- Enquiry form with name, email, phone (optional if not required), subject, and message
- Server-side validation, spam protection, and a success state

### 5.9 Eye health articles (`/health-resources`)
Include:
- Article listing and article detail pages (`/health-resources/{slug}`)
- Categories, author/editor field where supplied, and publication date
- Content management through the admin dashboard

Content must be reviewed and approved by the business. Add a clear note that educational content is not a substitute for professional examination or diagnosis.

### 5.10 Authentication pages
If customer accounts are enabled:
- Customer registration
- Customer login/logout
- Password reset
- Customer account profile
- Customer appointment list and appointment details

Allow public browsing without an account. Do not force registration for ordinary product browsing or general enquiries.

## 6. Customer account area

Implement customer accounts only if confirmed as part of the business requirements. If enabled, customers should be able to:
- Register and sign in securely.
- Update basic contact information.
- View their own appointment requests and statuses.
- Request cancellation or rescheduling, subject to clinic policy.
- View their own enquiry history if this feature is included.

Customers must never be able to view another customer's information. Enforce ownership checks on the server, not only in the interface.

## 7. Admin dashboard

Create a protected admin area under `/admin`. It must not be accessible to guests or ordinary customers.

### Dashboard (`/admin`)
Display useful summaries such as:
- Pending appointment requests
- Confirmed appointments
- Recent enquiries
- Active services
- Published products
- Recent administrative activity, if audit logging is implemented

Avoid decorative statistics that are not calculated from real database records.

### Admin modules

1. **Appointments**
   - List, search, filter, and view appointment requests.
   - Filter by status, branch, service, and date.
   - Approve/confirm, propose a new time, decline, or cancel.
   - Add internal notes that are never visible to customers.
   - Record status changes and the staff member responsible.
   - Prevent conflicting confirmed appointments according to the clinic's scheduling rules.

2. **Customers**
   - View customer contact details and relevant appointment history.
   - Edit basic information where authorised.
   - Deactivate accounts where appropriate.
   - Do not expose or create unnecessary clinical records.

3. **Services**
   - Create, edit, publish/unpublish, and archive services.
   - Set service descriptions and associate services with branches.
   - Manage display order and optional images.

4. **Products**
   - Create, edit, publish/unpublish, and archive catalogue items.
   - Manage categories, brand, descriptions, images, and availability labels.
   - Validate image type and size; store uploads safely.
   - Do not invent prices or stock levels. Make these fields optional or configurable based on the business's needs.

5. **Branches**
   - Create and edit branch details.
   - Manage opening hours and contact information.
   - Associate services with branches.

6. **Enquiries**
   - View, search, filter, assign or mark enquiries as new, in progress, or resolved.
   - Add private internal notes.
   - Do not expose internal notes to customers.

7. **Health resources**
   - Create, edit, preview, publish, unpublish, and archive articles.
   - Support draft and published states.
   - Sanitize rich text or use safe Markdown rendering.

8. **Staff and roles**
   - Create and deactivate staff accounts.
   - Assign roles and permissions.
   - Do not allow staff to promote themselves or grant permissions they do not have.

9. **Settings**
   - Manage business contact details, appointment rules, notification settings, and other approved configuration.
   - Restrict sensitive settings to the appropriate role.

10. **Reports**
   - Provide date-filtered summaries of appointment requests, appointment outcomes, enquiries, and catalogue activity.
   - Report only data that the system actually stores.
   - Restrict exports to authorised roles and avoid exporting unnecessary personal information.

## 8. Roles and permissions

Implement role-based access control. Start with these roles and refine them after requirements gathering:

- **Super Admin:** Full system configuration, staff management, and all modules.
- **Administrator:** Manage day-to-day business content, appointments, enquiries, products, services, and branches.
- **Receptionist:** Manage appointment requests and customer enquiries; access only the customer details needed for those tasks.
- **Content Manager:** Manage website pages, services, products, and articles, without access to private appointment information unless explicitly granted.
- **Customer:** Manage only their own profile and their own appointment requests, if customer accounts are enabled.
- **Guest:** Browse public pages and submit public forms.

Use Laravel policies/gates or an established role-permission package. Check permissions on every protected server action and route.

## 9. Database design

Create migrations, models, relationships, factories, and seeders for a normalised relational database. Use InnoDB, foreign keys, appropriate indexes, timestamps, and soft deletes where they make sense.

The following is the proposed logical schema. Refine field types and constraints during implementation. Do not create tables for functionality that the business has not approved.

### 9.1 `users`
Stores customer and staff login accounts.

Suggested fields:
- `id`
- `first_name`
- `last_name`
- `email` (unique, nullable only if an approved phone-only login design is chosen)
- `phone`
- `password`
- `account_type` or role relationship
- `is_active`
- `email_verified_at`
- `last_login_at` (optional)
- `remember_token`
- `created_at`
- `updated_at`
- `deleted_at` (optional)

Use Laravel's secure password hashing. Do not store plain-text passwords.

### 9.2 `roles`
- `id`
- `name` (unique)
- `slug` (unique)
- `description`
- timestamps

### 9.3 `role_user` or equivalent role assignment table
- `user_id`
- `role_id`
- timestamps

If using a permissions package, follow its schema rather than creating a competing custom system.

### 9.4 `branches`
- `id`
- `name`
- `slug` (unique)
- `address`
- `city`
- `region`
- `phone`
- `email` (nullable)
- `map_url` (nullable)
- `description` (nullable)
- `is_active`
- timestamps
- `deleted_at` (optional)

### 9.5 `branch_opening_hours`
- `id`
- `branch_id`
- `day_of_week`
- `opens_at` (nullable)
- `closes_at` (nullable)
- `is_closed`
- timestamps

Add a unique constraint on the appropriate branch/day combination. Support closed days.

### 9.6 `services`
- `id`
- `name`
- `slug` (unique)
- `short_description`
- `description`
- `image_path` (nullable)
- `is_published`
- `display_order`
- timestamps
- `deleted_at` (optional)

### 9.7 `branch_service`
Many-to-many association:
- `branch_id`
- `service_id`
- timestamps

Use a composite unique constraint on `branch_id` and `service_id`.

### 9.8 `product_categories`
- `id`
- `name`
- `slug` (unique)
- `description` (nullable)
- `is_active`
- timestamps

### 9.9 `products`
- `id`
- `product_category_id`
- `name`
- `slug` (unique)
- `brand` (nullable)
- `short_description` (nullable)
- `description` (nullable)
- `image_path` (nullable)
- `price` (nullable; only if supplied and approved)
- `availability_status` (configurable values such as available, enquire, unavailable)
- `is_published`
- timestamps
- `deleted_at` (optional)

Do not assume that the centre sells online or that a displayed product is in stock.

### 9.10 `appointments`
- `id`
- `reference_code` (unique, non-sequential or otherwise difficult to guess)
- `user_id` (nullable for guest bookings)
- `branch_id`
- `service_id`
- `customer_name`
- `customer_email`
- `customer_phone`
- `requested_date`
- `requested_time` (nullable if the customer selects a time range)
- `confirmed_start_at` (nullable)
- `confirmed_end_at` (nullable)
- `status` (pending, confirmed, reschedule_requested, declined, cancelled, completed, no_show)
- `customer_note` (nullable, length-limited, with warning not to enter sensitive medical information)
- `staff_note` (nullable and private)
- `handled_by` (nullable staff user ID)
- `status_updated_at` (nullable)
- timestamps

Add indexes for `reference_code`, `status`, `requested_date`, `branch_id`, `service_id`, and relevant customer fields. Add appropriate constraints and transactional conflict checks for confirmed appointments. Define the clinic's actual slot duration, buffer time, capacity, and operating rules before enabling automatic availability.

### 9.11 `appointment_status_histories`
- `id`
- `appointment_id`
- `old_status` (nullable)
- `new_status`
- `changed_by` (nullable staff user ID)
- `internal_note` (nullable)
- `created_at`

This table provides a history of appointment status changes.

### 9.12 `enquiries`
- `id`
- `user_id` (nullable)
- `name`
- `email`
- `phone` (nullable)
- `subject`
- `message`
- `status` (new, in_progress, resolved, spam)
- `assigned_to` (nullable staff user ID)
- timestamps

### 9.13 `enquiry_notes`
- `id`
- `enquiry_id`
- `user_id` (staff author)
- `note`
- timestamps

Keep these notes private to authorised staff.

### 9.14 `health_articles`
- `id`
- `title`
- `slug` (unique)
- `excerpt` (nullable)
- `body`
- `featured_image_path` (nullable)
- `author_id` (nullable staff user ID)
- `status` (draft, published, archived)
- `published_at` (nullable)
- timestamps
- `deleted_at` (optional)

### 9.15 `business_settings`
Use key/value settings or a typed settings structure for:
- Public business name
- General contact details
- Appointment lead time
- Booking window
- Notification preferences
- Other approved business settings

Restrict access to settings that affect security or business operations.

### 9.16 `notifications` and delivery records
Use Laravel's notification system where suitable. Store only the notification data required for delivery and history. Do not put sensitive medical details in email or SMS content.

### 9.17 Optional `audit_logs`
If required, record:
- Actor user ID
- Action
- Entity type and ID
- Timestamp
- Safe metadata

Do not log passwords, session secrets, full sensitive form contents, or unnecessary personal information.

### Database relationship summary

- A user may submit many appointments.
- A branch has many appointments.
- A service has many appointments.
- A branch offers many services; a service may be offered at many branches.
- A product belongs to one product category.
- An appointment has many status-history records.
- A staff user may handle many appointments and enquiries.
- An enquiry may have many private staff notes.
- A staff user may author many health articles.

Use Eloquent relationships and eager loading to avoid N+1 query problems.

## 10. Application routes and structure

Use named Laravel routes, route groups, middleware, controllers, form requests, policies, and service classes where appropriate.

Suggested route groups:

### Public routes
- `GET /`
- `GET /about`
- `GET /services`
- `GET /services/{slug}`
- `GET /products`
- `GET /products/{slug}`
- `GET /branches`
- `GET /branches/{slug}`
- `GET /health-resources`
- `GET /health-resources/{slug}`
- `GET /contact`
- `POST /contact`
- `GET /appointments/book`
- `POST /appointments`
- `GET /appointments/received`

### Customer routes (authentication required if accounts are enabled)
- `GET /account`
- `GET /account/profile`
- `PATCH /account/profile`
- `GET /account/appointments`
- `GET /account/appointments/{appointment}`
- `POST /account/appointments/{appointment}/cancel`
- `POST /account/appointments/{appointment}/request-reschedule`

Ensure the authenticated user owns the requested appointment before returning or changing it.

### Admin routes
- `GET /admin`
- `GET /admin/appointments`
- `GET /admin/appointments/{appointment}`
- `PATCH /admin/appointments/{appointment}`
- `GET|POST /admin/services`
- `GET|PATCH|DELETE /admin/services/{service}`
- Equivalent resource routes for products, categories, branches, articles, enquiries, staff, and settings.

Use Laravel resource controllers where they make the code clearer. Do not expose destructive actions through GET requests.

## 11. Core business rules

Implement and test these rules:

### Appointment rules
- Every new public appointment submission starts as `pending`.
- A request is not confirmed until an authorised staff member confirms it.
- Customers cannot set their own appointment status to confirmed or completed.
- Staff actions must be permission-checked and recorded.
- Do not allow appointments in the past.
- Apply branch opening hours and business-configured booking rules.
- Prevent overlapping confirmed appointments when the clinic's capacity rules require it.
- Use database transactions and appropriate locking/constraints to reduce race conditions.
- A customer can only view or request changes to their own appointment.
- Cancellation and rescheduling must follow business-defined cut-off rules.

### Product rules
- Only published products appear publicly.
- Product enquiry is available even when online checkout is not enabled.
- Price and stock status must be sourced from approved business data.
- Validate uploaded image formats and file sizes.

### Content rules
- Only published services, products, branches, and articles appear on public pages.
- Draft content is visible only to authorised staff.
- All public business claims and clinical information require business approval.

### Enquiry rules
- New enquiries default to `new`.
- Staff-only notes must never appear in customer-facing responses.
- Add basic rate limiting and spam protection.

## 12. Notifications

Build notification support behind a configurable service so the application can use email initially and add SMS or other channels later.

Potential notifications:
- Appointment request received
- Appointment confirmed
- Appointment reschedule proposed
- Appointment declined or cancelled
- Enquiry received (optional acknowledgement)
- Staff notification of a new appointment or enquiry

Requirements:
- Do not claim an email or SMS was sent if delivery is not configured or fails.
- Keep message content minimal.
- Never include detailed medical information in notifications.
- Store delivery outcomes where useful.
- Provide a development mail-log or local testing configuration.

## 13. Security and privacy requirements

Treat customer and patient-related information as sensitive.

Implement:
- HTTPS in production.
- CSRF protection.
- Server-side validation for every write operation.
- Secure password hashing and password reset.
- Role-based authorisation on all admin routes and actions.
- Ownership checks for customer records and appointments.
- Rate limiting on login, appointment, and contact forms.
- Secure session and cookie settings.
- Safe file upload validation and storage.
- Output escaping to prevent cross-site scripting.
- Parameterised database access through Eloquent/query builder.
- Protection against mass assignment.
- Secrets only in environment variables.
- Least-privilege database credentials.
- Appropriate backups and recovery documentation.
- Audit trails for sensitive administrative changes where appropriate.

Do not implement a clinical record or electronic medical record system in this project unless explicitly scoped, approved, and reviewed by qualified stakeholders. The initial appointment form should not request diagnosis, medication, detailed medical history, or other unnecessary clinical data.

Provide a privacy notice and consent wording for business review. The implementation must be checked against applicable Ghanaian data-protection and healthcare requirements by the business or an appropriate professional. Do not claim legal compliance without review.

## 14. Accessibility, SEO, and performance

### Accessibility
- Use semantic HTML.
- Ensure keyboard access to navigation, menus, forms, and dialogs.
- Provide visible focus indicators.
- Associate labels and error messages with form fields.
- Use sufficient colour contrast.
- Provide meaningful alt text.
- Respect reduced-motion preferences.
- Do not communicate status using colour alone.

### SEO
- Unique page titles and meta descriptions.
- Clean slugs.
- Canonical URLs where appropriate.
- Sitemap and robots configuration.
- Open Graph metadata.
- Structured data only when accurate and supported by verified business information.
- Do not add fake reviews, ratings, addresses, or opening hours.

### Performance
- Optimise and appropriately size images.
- Use lazy loading for below-the-fold images.
- Paginate large lists.
- Add database indexes for frequent filters.
- Avoid unnecessary JavaScript and large dependencies.
- Cache public content where appropriate without caching private customer data.

## 15. Testing requirements

Create automated tests for at least the following:

### Public site
- Public pages load successfully.
- Only published services, products, and articles are visible.
- Search and category filters return correct results.
- Forms validate required and invalid inputs.

### Appointments
- A valid request is saved with `pending` status.
- Past dates are rejected.
- Invalid branch/service combinations are rejected.
- A guest cannot access another customer's appointment.
- A customer cannot confirm or complete an appointment.
- An unauthorised staff member cannot manage appointments.
- Conflicting confirmed appointments are handled according to the configured capacity rules.
- Status history is created when status changes.

### Admin
- Guests cannot access admin pages.
- Customers cannot access admin pages.
- Each staff role can access only permitted modules and actions.
- Product, service, branch, and article CRUD operations work.
- Draft content is not publicly visible.
- File uploads reject invalid formats and oversized files.

### Security and quality
- CSRF and validation protections are active.
- Sensitive values are not exposed in public responses.
- Customer ownership checks are enforced.
- No secrets are committed to the repository.
- Critical user journeys work on mobile and desktop.

## 16. Seed data and content placeholders

Create seeders for:
- Initial roles and permissions.
- One initial super-admin account created securely through environment configuration or a documented command.
- Sample branches, services, categories, products, and articles only for local development.

Clearly mark all sample content as **DEMO DATA — REPLACE BEFORE PRODUCTION**. Never present invented branch addresses, phone numbers, staff biographies, opening hours, prices, stock, testimonials, or service claims as real.

Do not commit a default production password. Document how the first administrator is created and how credentials are changed.

## 17. Suggested project folder organisation

Follow Laravel conventions and keep responsibilities separated. A suggested structure is:

```text
app/
  Http/
    Controllers/
      Public/
      Customer/
      Admin/
    Requests/
      Appointment/
      Contact/
      Admin/
    Middleware/
  Models/
  Policies/
  Notifications/
  Services/
    AppointmentService.php
    AvailabilityService.php
    EnquiryService.php
resources/
  views/
    layouts/
    components/
    public/
      home.blade.php
      about.blade.php
      services/
      products/
      branches/
      appointments/
      contact/
      health-resources/
    customer/
    admin/
      dashboard/
      appointments/
      customers/
      services/
      products/
      branches/
      enquiries/
      articles/
      staff/
      settings/
database/
  migrations/
  factories/
  seeders/
routes/
  web.php
  auth.php
tests/
  Feature/
  Unit/
public/
  images/
  build/
```

Adjust the structure to Laravel conventions and the selected authentication/authorisation packages. Do not create empty folders or classes without a purpose.

## 18. Required deliverables

Produce:

1. A working Laravel application with the agreed public pages.
2. A responsive customer appointment and enquiry experience.
3. A secure admin dashboard with role-based access.
4. Database migrations, models, relationships, factories, and seeders.
5. Server-side validation and tested business rules.
6. Automated feature and unit tests for critical workflows.
7. `.env.example` with placeholder values only.
8. A `README.md` explaining prerequisites, installation, environment setup, migrations, seeders, tests, and deployment.
9. A list of all demo content that must be replaced with verified business information.
10. A concise explanation of architecture, database relationships, and important design decisions.

## 19. Development sequence

Implement in this order. Do not attempt to generate the entire application in one unreviewed code dump.

### Phase 1 — Repository inspection and foundation
- Inspect the repository and current environment.
- Confirm PHP, Composer, Node, and database versions.
- Set up or preserve Laravel.
- Configure environment variables and database connection.
- Establish the base layout, navigation, colour tokens, and reusable components.
- Confirm the application runs.

### Phase 2 — Database and authentication
- Implement migrations and Eloquent models.
- Define relationships and indexes.
- Add authentication.
- Add roles, permissions, policies, and protected route groups.
- Add development seeders.

### Phase 3 — Public website
- Implement home, about, services, products, branches, contact, and health-resource pages.
- Add search, filters, pagination, and responsive layouts.
- Use clearly labelled demo data until business content is confirmed.

### Phase 4 — Appointment and enquiry workflows
- Implement appointment request submission and validation.
- Implement appointment reference and acknowledgement page.
- Implement staff review and status transitions.
- Implement enquiry submission and staff management.
- Add notifications behind configurable channels.

### Phase 5 — Admin dashboard
- Implement dashboard summaries from real database data.
- Implement CRUD for services, products, categories, branches, and articles.
- Implement appointment and enquiry management.
- Implement staff management and role restrictions.
- Add reports only for data that is genuinely captured.

### Phase 6 — Quality assurance
- Write and run automated tests.
- Test all roles and permissions.
- Test mobile and desktop layouts.
- Check accessibility, SEO, performance, and security.
- Fix errors before moving to deployment.

### Phase 7 — Deployment and handover
- Document production configuration.
- Configure HTTPS, storage permissions, database, backups, and scheduled tasks if used.
- Create the first administrator securely.
- Replace demo content with business-approved content.
- Provide setup and maintenance instructions.

At the end of each phase:
1. Summarise what was implemented.
2. List files created or changed.
3. Explain how to run and test the work.
4. Report known issues or decisions still needed.
5. Do not claim a feature is complete unless it has been implemented and tested.

## 20. Important instructions for the coding agent

- Start by inspecting the existing repository. Do not assume it is empty.
- If a requirement is unclear, identify the assumption and ask only for information that blocks implementation. Use safe, clearly labelled placeholders for content that can be added later.
- Do not fabricate company information or clinical claims.
- Do not add payment processing, e-commerce checkout, medical records, live chat, or SMS integrations unless specifically approved.
- Do not expose private customer information in URLs, page source, logs, or public API responses.
- Do not use mock data to make production dashboards appear functional.
- Do not leave buttons that look interactive but do nothing.
- Every form must have validation, clear success/error feedback, and a working backend action.
- Use reusable components and maintainable code rather than duplicating page logic.
- Keep the application accessible and responsive.
- Explain important architectural decisions in plain language.
- Prioritise a secure, working minimum viable product over a large number of incomplete features.

## 21. Definition of done

The first production-ready release is complete only when:
- The public pages work and display business-approved content.
- Visitors can submit appointment requests and enquiries.
- Requests are stored correctly and appear in the staff dashboard.
- Staff can manage requests according to their permissions.
- Services, products, branches, and articles can be maintained through the admin interface.
- Customers cannot access other customers' information.
- Appointment requests are not falsely represented as confirmed.
- Critical workflows have passing automated tests.
- The site is responsive, accessible, and uses HTTPS in production.
- Setup, deployment, backup, and administrator instructions are documented.

**Build the application as a trustworthy, welcoming, maintainable digital platform for Innes Eye & Visioncare Centre.**
