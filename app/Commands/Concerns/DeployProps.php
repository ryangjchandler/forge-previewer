<?php

namespace App\Commands\Concerns;

class DeployProps
{
    const TOKEN = 'token';
    const SERVER = 'server';
    const REPO = 'repo';
    const BRANCH = 'branch';
    const DOMAIN = 'domain';
    const PROVIDER = 'provider';
    const PHP_VERSION = 'php-version';
    const COMMAND = 'command';
    const EDIT_ENV = 'edit-env';
    const SCHEDULER = 'scheduler';
    const ISOLATE = 'isolate';
    const CI = 'ci';
    const NO_QUICK_DEPLOY = 'no-quick-deploy';
    const NO_DEPLOY = 'no-deploy';
    const NO_DB = 'no-db';

    public static function toArray(): array
    {
        return [
            self::TOKEN,
            self::SERVER,
            self::REPO,
            self::BRANCH,
            self::DOMAIN,
            self::PROVIDER,
            self::PHP_VERSION,
            self::COMMAND,
            self::EDIT_ENV,
            self::SCHEDULER,
            self::ISOLATE,
            self::CI,
            self::NO_QUICK_DEPLOY,
            self::NO_DEPLOY,
            self::NO_DB,
        ];
    }
}
