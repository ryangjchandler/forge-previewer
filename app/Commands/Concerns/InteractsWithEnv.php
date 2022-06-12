<?php

namespace App\Commands\Concerns;

trait InteractsWithEnv
{
    protected function env(string $name, mixed $or = null): mixed
    {
        if (env($name) !== null) {
            return env($name);
        }

        return $or;
    }
}
