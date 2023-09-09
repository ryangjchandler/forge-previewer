<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => 'Forge-previewer',

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This value determines the "version" your application is currently running
    | in. You may want to follow the "Semantic Versioning" - Given a version
    | number MAJOR.MINOR.PATCH when an update happens: https://semver.org.
    |
    */

    'version' => app('git.version'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. This can be overridden using
    | the global command line "--env" option when calling commands.
    |
    */

    'env' => 'development',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],

    'token' => env('FORGE_TOKEN'),

    'server' => env('FORGE_SERVER'),

    'repo' => env('FORGE_REPO'),

    'branch' => env('FORGE_BRANCH'),

    'domain' => env('FORGE_DOMAIN'),

    'provider' => env('PREVIEWER_PROVIDER', 'github'),

    'php-version' => env('PREVIEWER_PHP_VERSION', 'php81'),

    'command' => env('PREVIEWER_COMMAND'),

    'edit-env' => env('PREVIEWER_EDIT_ENV'),

    'scheduler' => env('PREVIEWER_SCHEDULER', false),

    'isolate' => env('PREVIEWER_ISOLATE', false),

    'ci' => env('PREVIEWER_CI', false),

    'no-quick-deploy' => env('PREVIEWER_NO_QUICK_DEPLOY', false),

    'no-deploy' => env('PREVIEWER_NO_DEPLOY', false),

    'no-db' => env('PREVIEWER_NO_DB', false),
];
