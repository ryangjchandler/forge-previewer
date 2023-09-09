<?php

namespace App\Commands\Concerns;

trait GeneratesSiteInfo
{
    protected function generateSiteDomain(string $branchName, string $domainName): string
    {
        return str($branchName)->slug()->append('.', $domainName)->toString();
    }
}
