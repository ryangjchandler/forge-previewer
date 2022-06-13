<?php

namespace App\Commands\Concerns;

trait GeneratesSiteInfo
{
    protected function generateSiteDomain(): string
    {
        return str($this->getBranchName())->slug()->append('.', $this->getDomainName())->toString();
    }
}
