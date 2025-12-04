<?php

declare(strict_types=1);

return [
    'app_env' => $_ENV['APP_ENV'] ?? 'dev',

    'debug' => isset($_ENV['DEBUG'])
        ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
        : false,

    'catalogue_url' => $_ENV['CATALOGUE_URL'],

    'jwt_secret' => $_ENV['JWT_SECRET'],

    'database' => [
        'dsn'      => $_ENV['PDO_URL'],
        'user'     => $_ENV['PDO_USER'],
        'password' => $_ENV['PDO_PASSWORD'],
    ],

    'logger' => [
        'name' => 'RatingsAPI',
    ],
];