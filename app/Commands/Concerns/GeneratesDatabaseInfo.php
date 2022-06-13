<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Str;

trait GeneratesDatabaseInfo
{
    protected function getDatabaseUserName(): string
    {
        return $this->getDatabaseName();
    }

    protected function getDatabasePassword(): string
    {
        static $password;

        return $password ??= Str::random(16);
    }

    protected function getDatabaseName(): string
    {
        return str($this->getBranchName())->slug('_')->limit(64)->toString();
    }
}
