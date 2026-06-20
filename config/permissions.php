<?php

/*
|--------------------------------------------------------------------------
| Dashboard permission catalog
|--------------------------------------------------------------------------
|
| Single source of truth for every dashboard permission. The PermissionSeeder
| creates these, and RoleController@permissionsCatalog exposes them (grouped,
| with Arabic labels) to the Roles & Permissions management UI.
|
| Shape: each group has a label and a map of permissionKey => label.
|
*/

return [

    'super_admin_role' => 'super_admin',

    'groups' => [

        'users' => [
            'label' => 'المستخدمين',
            'permissions' => [
                'users.show'   => 'عرض',
                'users.add'    => 'إضافة',
                'users.edit'   => 'تعديل',
                'users.remove' => 'حذف',
            ],
        ],

        'roles' => [
            'label' => 'الأدوار والصلاحيات',
            'permissions' => [
                'roles.show'   => 'عرض',
                'roles.add'    => 'إضافة',
                'roles.edit'   => 'تعديل',
                'roles.remove' => 'حذف',
            ],
        ],

        'discounts' => [
            'label' => 'الخصومات',
            'permissions' => [
                'discounts.show'   => 'عرض',
                'discounts.add'    => 'إضافة',
                'discounts.edit'   => 'تعديل',
                'discounts.remove' => 'حذف',
            ],
        ],

        'stock' => [
            'label' => 'إدارة المخزون',
            'permissions' => [
                'stock.show'   => 'عرض',
                'stock.update' => 'تعديل',
            ],
        ],

        'order_history' => [
            'label' => 'سجل الطلبات',
            'permissions' => [
                'order_history.show'   => 'عرض',
                'order_history.remove' => 'حذف',
            ],
        ],

        'live_orders' => [
            'label' => 'الطلبات المباشرة',
            'permissions' => [
                'live_orders.show'    => 'عرض',
                'live_orders.approve' => 'قبول',
                'live_orders.cancel'  => 'رفض/إلغاء',
            ],
        ],

        'categories' => [
            'label' => 'الأقسام',
            'permissions' => [
                'categories.show'   => 'عرض',
                'categories.add'    => 'إضافة',
                'categories.edit'   => 'تعديل',
                'categories.remove' => 'حذف',
            ],
        ],

        'items' => [
            'label' => 'الأصناف',
            'permissions' => [
                'items.show'   => 'عرض',
                'items.add'    => 'إضافة',
                'items.edit'   => 'تعديل',
                'items.remove' => 'حذف',
            ],
        ],

        'options' => [
            'label' => 'الخيارات المشتركة',
            'permissions' => [
                'options.show'   => 'عرض',
                'options.add'    => 'إضافة',
                'options.edit'   => 'تعديل',
                'options.remove' => 'حذف',
            ],
        ],

        'menus' => [
            'label' => 'القوائم ومجموعاتها',
            'permissions' => [
                'menus.show'   => 'عرض',
                'menus.add'    => 'إضافة',
                'menus.edit'   => 'تعديل',
                'menus.remove' => 'حذف',
            ],
        ],

        'headers' => [
            'label' => 'الهيدر الرئيسي',
            'permissions' => [
                'headers.show'   => 'عرض',
                'headers.add'    => 'إضافة',
                'headers.edit'   => 'تعديل',
                'headers.remove' => 'حذف',
            ],
        ],

        'numbers' => [
            'label' => 'أرقام التواصل',
            'permissions' => [
                'numbers.show'   => 'عرض',
                'numbers.add'    => 'إضافة',
                'numbers.delete' => 'حذف',
            ],
        ],

        'cancel_reasons' => [
            'label' => 'أسباب الإلغاء',
            'permissions' => [
                'cancel_reasons.show'   => 'عرض',
                'cancel_reasons.add'    => 'إضافة',
                'cancel_reasons.delete' => 'حذف',
            ],
        ],

        'general_settings' => [
            'label' => 'الإعدادات العامة',
            'permissions' => [
                'general_settings.show' => 'عرض',
                'general_settings.edit' => 'تعديل',
            ],
        ],

        'visa_settings' => [
            'label' => 'إعدادات الدفع الإلكتروني',
            'permissions' => [
                'visa_settings.show' => 'عرض',
                'visa_settings.edit' => 'تعديل',
            ],
        ],

        'colors_settings' => [
            'label' => 'إعدادات الألوان',
            'permissions' => [
                'colors_settings.show' => 'عرض',
                'colors_settings.edit' => 'تعديل',
            ],
        ],

        'working_periods' => [
            'label' => 'ساعات العمل ومجموعاتها',
            'permissions' => [
                'working_periods.show'   => 'عرض',
                'working_periods.add'    => 'إضافة',
                'working_periods.edit'   => 'تعديل',
                'working_periods.remove' => 'حذف',
            ],
        ],

        'branches' => [
            'label' => 'الفروع',
            'permissions' => [
                'branches.show'   => 'عرض',
                'branches.add'    => 'إضافة',
                'branches.edit'   => 'تعديل',
                'branches.remove' => 'حذف',
            ],
        ],

        'qr' => [
            'label' => 'رمز الاستجابة السريعة',
            'permissions' => [
                'qr.show' => 'عرض',
            ],
        ],

        'reports' => [
            'label' => 'التقارير',
            'permissions' => [
                'reports.show' => 'عرض',
            ],
        ],

        'logs' => [
            'label' => 'سجل النشاطات',
            'permissions' => [
                'logs.show' => 'عرض',
            ],
        ],

    ],
];
