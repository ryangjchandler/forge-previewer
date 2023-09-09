<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class DestroyConfiguration extends ConfigurationAbstract
{
    public ?string $forgeToken;
    public ?string $forgeServer;
    public ?string $repositoryName;
    public ?string $branchName;
    public ?string $domainName;

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
            $this->getFilledOptions($options, DestroyProps::toArray())
        );

        // Initialize the properties...
        $this->forgeToken = $config['token'];
        $this->forgeServer = $config['server'];
        $this->repositoryName = $config['repo'];
        $this->branchName = $config['branch'];
        $this->domainName = $config['domain'];
    }
}
