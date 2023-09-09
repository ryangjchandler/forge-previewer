<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Str;

trait GeneratesDatabaseInfo
{
    protected function getDatabaseUserName(string $branchName): string
    {
        return $this->getDatabaseName($branchName);
    }

    protected function getDatabasePassword(): string
    {
        static $password;

        return $password ??= Str::random(16);
    }

    protected function getDatabaseName(string $branchName): string
    {
        return str($branchName)->slug('_')->limit(64)->toString();
    }
}
