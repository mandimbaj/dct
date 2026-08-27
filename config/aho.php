<?php

return [
    'admin' => [
        'email' => env('AHO_ADMIN_EMAIL', 'jadicemandimba@gmail.com'),
    ],

    'notifications' => [
        'mail_enabled' => filter_var(env('AHO_ADMIN_EMAIL_NOTIFICATIONS', true), FILTER_VALIDATE_BOOLEAN),
        'activity_enabled' => filter_var(env('AHO_ADMIN_ACTIVITY_NOTIFICATIONS', true), FILTER_VALIDATE_BOOLEAN),
    ],
];
