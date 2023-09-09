<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Illuminate\Support\Arr;

abstract class ConfigurationAbstract
{
    public ?string $forgeToken;
    public ?string $forgeServer;
    public ?string $repositoryName;
    public ?string $branchName;
    public ?string $domainName;

    public function __construct(array $options = [])
    {
        $this->configureOptions($options);
    }

    abstract protected function configureOptions(array $options = []): void;

    protected function getFilledOptions(array $options, array $commandProperties): array
    {
        $filteredOptions = Arr::only($options, $commandProperties);

        return array_filter($filteredOptions, fn($value) => !is_null($value) && $value !== '' && $value !== []);
    }
}
