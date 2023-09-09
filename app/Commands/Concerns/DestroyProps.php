<?php

namespace App\Commands\Concerns;

class DestroyProps
{
    const TOKEN = 'token';
    const SERVER = 'server';
    const REPO = 'repo';
    const BRANCH = 'branch';
    const DOMAIN = 'domain';

    public static function toArray(): array
    {
        return [
            self::TOKEN,
            self::SERVER,
            self::REPO,
            self::BRANCH,
            self::DOMAIN,
        ];
    }
}
