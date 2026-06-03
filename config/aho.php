<?php

return [
    'admin' => [
        'email' => env('AHO_ADMIN_EMAIL', 'jadicemandimba@gmail.com'),
    ],

    'notifications' => [
        'mail_enabled' => (bool) env('AHO_ADMIN_EMAIL_NOTIFICATIONS', true),
        'activity_enabled' => (bool) env('AHO_ADMIN_ACTIVITY_NOTIFICATIONS', true),
    ],
];
