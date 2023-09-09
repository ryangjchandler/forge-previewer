<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeployConfiguration extends ConfigurationAbstract
{
    public ?string $providerName;
    public ?string $phpVersion;
    public ?array $commands;
    public ?array $environmentKeys;
    public bool $schedulerRequired;
    public bool $isolateRequired;
    public bool $ciOutputRequired;
    public bool $quickDeployRequired;
    public bool $deployRequired;
    public bool $databaseRequired;

    protected function configureOptions(array $options = []): void
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            DeployProps::TOKEN           => config('app.token'),
            DeployProps::SERVER          => config('app.server'),
            DeployProps::REPO            => config('app.repo'),
            DeployProps::BRANCH          => config('app.branch'),
            DeployProps::DOMAIN          => config('app.domain'),
            DeployProps::PROVIDER        => config('app.provider'),
            DeployProps::PHP_VERSION     => config('app.php-version'),
            DeployProps::COMMAND         => config('app.command'),
            DeployProps::EDIT_ENV        => config('app.edit-env'),
            DeployProps::SCHEDULER       => config('app.scheduler'),
            DeployProps::ISOLATE         => config('app.isolate'),
            DeployProps::CI              => config('app.ci'),
            DeployProps::NO_QUICK_DEPLOY => config('app.no-quick-deploy'),
            DeployProps::NO_DEPLOY       => config('app.no-deploy'),
            DeployProps::NO_DB           => config('app.no-db'),
        ]);

        $config = $resolver->resolve(
            $this->getFilledOptions($options, DeployProps::toArray())
        );

        // Initialize the properties...
        $this->forgeToken = $config['token'];
        $this->forgeServer = $config['server'];
        $this->repositoryName = $config['repo'];
        $this->branchName = $config['branch'];
        $this->domainName = $config['domain'];
        $this->providerName = $config['provider'];
        $this->phpVersion = $config['php-version'];
        $this->commands = $config['command'];
        $this->environmentKeys = $config['edit-env'];
        $this->schedulerRequired = ! $config['scheduler'];
        $this->isolateRequired = $config['isolate'];
        $this->ciOutputRequired = $config['ci'];
        $this->quickDeployRequired = ! $config['no-quick-deploy'];
        $this->deployRequired = ! $config['no-deploy'];
        $this->databaseRequired = ! $config['no-db'];
    }
}
