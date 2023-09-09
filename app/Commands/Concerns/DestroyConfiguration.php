<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Illuminate\Support\Arr;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DestroyConfiguration
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

    protected function configureOptions(array $options = []): void
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            DestroyProps::TOKEN           => config('app.token'),
            DestroyProps::SERVER          => config('app.server'),
            DestroyProps::REPO            => config('app.repo'),
            DestroyProps::BRANCH          => config('app.branch'),
            DestroyProps::DOMAIN          => config('app.domain'),
        ]);

        $config = $resolver->resolve(
            Arr::only($options, DestroyProps::toArray())
        );

        // Initialize the properties...
        $this->forgeToken = $config['token'];
        $this->forgeServer = $config['server'];
        $this->repositoryName = $config['repo'];
        $this->branchName = $config['branch'];
        $this->domainName = $config['domain'];
    }
}
