<?php

return [

    /*
    | Feature flags. Customer accounts are a proposed feature that still needs
    | business confirmation; switch off with FEATURE_CUSTOMER_ACCOUNTS=false.
    */
    'features' => [
        'customer_accounts' => (bool) env('FEATURE_CUSTOMER_ACCOUNTS', true),
    ],

    /*
    | Show a site-wide banner reminding reviewers that the content is demo data.
    | Turn off only once all demo content has been replaced with verified content.
    */
    'demo_notice' => (bool) env('SHOW_DEMO_NOTICE', true),

    /*
    | Optional first super-admin credentials, used by `php artisan innes:create-admin --from-env`.
    | Never commit real values. Remove them from the server .env after first use.
    */
    'initial_admin' => [
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
        'first_name' => env('INITIAL_ADMIN_FIRST_NAME', 'System'),
        'last_name' => env('INITIAL_ADMIN_LAST_NAME', 'Administrator'),
    ],

    'uploads' => [
        'disk' => env('UPLOADS_DISK', 'public'),
        'max_kb' => (int) env('UPLOAD_MAX_KB', 2048),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    | Permission catalogue. Each permission is checked through a Gate of the same name.
    */
    'permissions' => [
        'dashboard.view' => 'View the admin dashboard',
        'appointments.view' => 'View appointment requests',
        'appointments.manage' => 'Confirm, reschedule, decline and cancel appointments',
        'customers.view' => 'View customer contact details',
        'customers.manage' => 'Edit and deactivate customer accounts',
        'enquiries.view' => 'View enquiries',
        'enquiries.manage' => 'Update, assign and annotate enquiries',
        'services.manage' => 'Manage services',
        'products.manage' => 'Manage products and categories',
        'branches.manage' => 'Manage branches and opening hours',
        'articles.manage' => 'Manage health articles',
        'staff.manage' => 'Manage staff accounts and roles',
        'settings.general' => 'Manage public business details',
        'settings.system' => 'Manage appointment rules and notification settings',
        'reports.view' => 'View reports',
        'reports.export' => 'Export report summaries',
        'audit.view' => 'View the administrative activity log',
    ],

    /*
    | Roles and the permissions each grants. Super Admin implicitly has every permission.
    | Rank prevents staff from assigning roles more privileged than their own.
    */
    'roles' => [
        'super-admin' => [
            'name' => 'Super Admin',
            'rank' => 100,
            'description' => 'Full system configuration, staff management and all modules.',
            'permissions' => ['*'],
        ],
        'administrator' => [
            'name' => 'Administrator',
            'rank' => 80,
            'description' => 'Day-to-day business content, appointments, enquiries, products, services and branches.',
            'permissions' => [
                'dashboard.view', 'appointments.view', 'appointments.manage', 'customers.view', 'customers.manage',
                'enquiries.view', 'enquiries.manage', 'services.manage', 'products.manage', 'branches.manage',
                'articles.manage', 'settings.general', 'reports.view', 'reports.export', 'audit.view',
            ],
        ],
        'receptionist' => [
            'name' => 'Receptionist',
            'rank' => 40,
            'description' => 'Appointment requests and customer enquiries, with the customer details needed for those tasks.',
            'permissions' => [
                'dashboard.view', 'appointments.view', 'appointments.manage', 'customers.view',
                'enquiries.view', 'enquiries.manage',
            ],
        ],
        'content-manager' => [
            'name' => 'Content Manager',
            'rank' => 40,
            'description' => 'Website content: services, products and articles. No access to private appointment information.',
            'permissions' => [
                'dashboard.view', 'services.manage', 'products.manage', 'articles.manage',
            ],
        ],
    ],

    /*
    | Defaults for business settings. Editable in Admin → Settings; these are only fallbacks.
    */
    'settings_defaults' => [
        'business_name' => 'Innes Eye & Visioncare Centre',
        'tagline' => 'Trusted eye care and stylish vision solutions',
        'contact_email' => null,
        'contact_phone' => null,
        'whatsapp' => null,
        'facebook_url' => null,
        'instagram_url' => null,
        'response_time_note' => null,
        'about_intro' => null,
        'mission' => null,
        'vision' => null,
        'values' => null,
        'booking_lead_time_hours' => 24,
        'booking_window_days' => 60,
        'slot_duration_minutes' => 30,
        'slot_capacity' => 1,
        'cancellation_cutoff_hours' => 24,
        'notify_customer_emails' => true,
        'notify_staff_email' => null,
    ],
];
