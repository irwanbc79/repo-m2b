<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    | Definisi role dan permission untuk M2B Portal
    */

    'roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'level' => 100,
            'permissions' => ['*'], // All permissions
        ],
        'director' => [
            'label' => 'Director/Owner', 
            'level' => 90,
            'permissions' => ['*'], // All permissions
        ],
        'manager' => [
            'label' => 'Manager',
            'level' => 80,
            'permissions' => [
                'dashboard.*',
                'shipment.*',
                'invoice.*',
                'customer.*',
                'vendor.*',
                'quotation.*',
                'job_costing.*',
                'cashier.view', 'cashier.input',
                'job_costing.*',
                'report.*',
                'user.view',
            ],
        ],
        'supervisor' => [
            'label' => 'Supervisor',
            'level' => 70,
            'permissions' => [
                'dashboard.*',
                'shipment.*',
                'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.send',
                'customer.view', 'customer.edit',
                'vendor.view', 'vendor.edit',
                'quotation.*',
                'job_costing.view', 'job_costing.create',
                'report.view_basic',
            ],
        ],
        'staff_accounting' => [
            'label' => 'Staff Accounting',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'invoice.*',
                'cashier.*',
                'job_costing.*',
                'report.view_financial',
            ],
        ],
        'staff_operations' => [
            'label' => 'Staff Operations',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'shipment.*',
                'quotation.*',
                'vendor.view',
                'job_costing.view', 'job_costing.create',
            ],
        ],
        'staff_sales' => [
            'label' => 'Staff Sales/Marketing',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'customer.*',
                'quotation.*',
                'invoice.view', 'invoice.create', 'invoice.send',
            ],
        ],
        'staff_ppjk' => [
            'label' => 'Staff PPJK/Customs',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'shipment.*',
                'hs_code.*',
                'documentation.*',
            ],
        ],
        'staff_documentation' => [
            'label' => 'Staff Documentation',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'shipment.view', 'shipment.edit',
                'documentation.*',
                'customer.communicate',
                'vendor.communicate',
            ],
        ],
        'cashier' => [
            'label' => 'Kasir',
            'level' => 50,
            'permissions' => [
                'dashboard.view',
                'cashier.*',
                'invoice.view', 'invoice.verify_payment',
            ],
        ],
        'staff' => [
            'label' => 'Staff',
            'level' => 40,
            'permissions' => [
                'dashboard.view',
                'shipment.view',
                'invoice.view',
                'customer.view',
            ],
        ],
        'admin' => [
            'label' => 'Admin',
            'level' => 60,
            'permissions' => [
                'dashboard.*',
                'shipment.*',
                'invoice.*',
                'customer.*',
                'vendor.*',
                'quotation.*',
                'user.view', 'user.create', 'user.edit',
                'cashier.view', 'cashier.input',
                'job_costing.*',
            ],
        ],
        'customer' => [
            'label' => 'Customer',
            'level' => 10,
            'permissions' => [
                'customer_portal.*',
            ],
        ],
        'customer_viewer' => [
            'label' => 'Customer (View Only)',
            'level' => 5,
            'permissions' => [
                'customer_portal.view',
                'customer_portal.download',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Groups (untuk UI)
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'dashboard' => ['view', 'analytics'],
        'shipment' => ['view', 'create', 'edit', 'delete', 'assign'],
        'invoice' => ['view', 'create', 'edit', 'delete', 'send', 'claim', 'verify_payment', 'revise'],
        'customer' => ['view', 'create', 'edit', 'delete', 'communicate'],
        'vendor' => ['view', 'create', 'edit', 'delete', 'communicate'],
        'quotation' => ['view', 'create', 'edit', 'delete', 'send'],
        'cashier' => ['view', 'input', 'verify', 'journal', 'revise'],
        'job_costing' => ['view', 'create', 'edit', 'delete', 'approve'],
        'report' => ['view_basic', 'view_financial', 'export'],
        'user' => ['view', 'create', 'edit', 'delete'],
        'settings' => ['view', 'edit'],
        'hs_code' => ['view', 'search'],
        'documentation' => ['view', 'create', 'edit', 'upload'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Access (untuk sidebar)
    |--------------------------------------------------------------------------
    */
    'menu_access' => [
        'dashboard' => ['dashboard.view'],
        'email_inbox' => ['dashboard.view'],
        'manage_shipments' => ['shipment.view'],
        'kalkulator_pabean' => ['shipment.view', 'hs_code.view'],
        'hs_code_explorer' => ['hs_code.view'],
        'dokumentasi_lapangan' => ['documentation.view'],
        'manage_customers' => ['customer.view'],
        'manage_vendors' => ['vendor.view'],
        'quotation' => ['quotation.view'],
        'invoicing' => ['invoice.view'],
        'pembukuan_bank' => ['cashier.view'],
        'job_costing' => ['job_costing.view'],
        'journal_entries' => ['cashier.journal'],
        'reports' => ['report.view_basic'],
        'user_management' => ['user.view'],
        'settings' => ['settings.view'],
    ],
];
