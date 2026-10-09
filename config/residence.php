<?php

return [

    'timezone' => 'Africa/Kinshasa',

    'phone_country_code' => '243',

    'currencies' => [
        'USD' => ['label' => 'Dollar américain', 'symbol' => 'USD', 'decimals' => 2],
        'CDF' => ['label' => 'Franc congolais', 'symbol' => 'FC', 'decimals' => 2],
    ],

    'organization_defaults' => [
        'generation_day' => 1,
        'due_day' => 30,
        'grace_until_day' => 5,
        'prorata_method' => 'daily',
        'reminder_days_before' => 3,
        'reminder_repeat_days' => 3,
        'move_out_notice_days' => 3,
        'default_currency' => 'USD',
        'enabled_currencies' => ['USD', 'CDF'],
        'share_declaration_trace' => false,
    ],

    'unit_types' => [
        'room' => 'Chambre',
        'studio' => 'Studio',
        'apartment' => 'Appartement',
        'house' => 'Maison',
        'office' => 'Bureau',
    ],

    'expense_categories' => [
        'maintenance' => 'Maintenance',
        'utilities' => 'Charges',
        'tax' => 'Taxes',
        'salary' => 'Salaires',
        'supplies' => 'Fournitures',
        'other' => 'Autre',
    ],

];
