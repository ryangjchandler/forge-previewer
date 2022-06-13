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

    protected function getDomainName()
    {
        return $this->env('FORGE_DOMAIN', or: $this->option('domain'));
    }

    protected function getForgeServer()
    {
        return $this->env('FORGE_SERVER', or: $this->option('server'));
    }

    protected function getRepoName()
    {
        return $this->env('FORGE_REPO', or: $this->option('repo'));
    }

    protected function getBranchName()
    {
        return $this->env('FORGE_BRANCH', or: $this->option('branch'));
    }

    protected function getForgeToken(): ?string
    {
        return $this->env('FORGE_TOKEN', or: $this->option('token'));
    }
}
