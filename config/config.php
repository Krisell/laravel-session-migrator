<?php

return [
    'migrate' => [
        'serialization' => env('SESSION_MIGRATE_SERIALIZATION', false),
        'driver' => env('SESSION_MIGRATE_DRIVER', null),
    ],
];
