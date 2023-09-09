<?php

namespace App\Commands;

use App\Commands\Concerns\DeployConfiguration;
use App\Commands\Concerns\GeneratesDatabaseInfo;
use App\Commands\Concerns\GeneratesSiteInfo;
use Exception;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Site;
use Laravel\Forge\Resources\Server;
use App\Commands\Concerns\HandlesOutput;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class DeployCommand extends Command
{
    use HandlesOutput;
    use GeneratesSiteInfo;
    use GeneratesDatabaseInfo;

    protected $signature = 'deploy
        {--token=  : The Forge API token.}
        {--server= : The ID of the target server.}
        {--provider=github : The Git provider.}
        {--repo= : The name of the repository being deployed.}
        {--branch= : The name of the branch being deployed.}
        {--domain= : The domain you\'d like to use for deployments.}
        {--php-version=php81 : The version of PHP the site should use, e.g. php81, php80, ...}
        {--command=* : A command you would like to execute on the site, e.g. php artisan db:seed.}
        {--edit-env=* : The colon-separated name and value that will be added/updated in the site\'s environment, e.g. "MY_API_KEY:my_api_key_value".}
        {--scheduler : Setup a cronjob to run Laravel\'s scheduler.}
        {--isolate : Enable site isolation.}
        {--ci : Add additional output for your CI provider.}
        {--no-quick-deploy : Create your site without "Quick Deploy".}
        {--no-deploy : Avoid deploying the site.}
        {--no-db : Avoid creating a database.}';

    protected $description = 'Deploy a branch / pull request to Laravel Forge.';

    protected Forge $forge;
    protected DeployConfiguration $config;

    public function handle(Forge $forge)
    {
        $this->resolveConfiguration();

        $this->forge = $forge->setApiKey($this->config->forgeToken);

        try {
            $server = $forge->server($this->config->forgeServer);
        } catch (Exception $_) {
            return $this->fail("Failed to find server.");
        }

        $site = $this->findOrCreateSite($server);

        if ($this->config->databaseRequired) {
            $this->maybeCreateDatabase($server, $site);
        }

        if ($this->config->environmentKeys) {
            $this->information('Updating environment variables');

            $envSource = $forge->siteEnvironmentFile($server->id, $site->id);

            foreach ($this->config->environmentKeys as $env) {
                [$key, $value] = explode(':', $env, 2);

                $envSource = $this->updateEnvVariable($key, $value, $envSource);
            }

            $forge->updateSiteEnvironmentFile($server->id, $site->id, $envSource);
        }

        $this->information('Deploying');

        $site->deploySite();

        foreach ($this->config->commands as $i => $command) {
            if ($i === 0) {
                $this->information('Executing site command(s)');
            }

            $forge->executeSiteCommand($server->id, $site->id, [
                'command' => $command,
            ]);
        }

        $this->maybeCreateScheduledJob($server);
    }

    protected function updateEnvVariable(string $name, string $value, string $source): string
    {
        if (! str_contains($source, "{$name}=")) {
            $source .= PHP_EOL . "{$name}={$value}";
        } else {
            $source = preg_replace("/^{$name}=[^\r\n]*/m", "{$name}={$value}", $source, 1);
        }

        return $source;
    }

    protected function maybeCreateScheduledJob(Server $server)
    {
        if ($this->config->schedulerRequired) {
            return;
        }

        $command = $this->buildScheduledJobCommand();

        foreach ($this->forge->jobs($server->id) as $job) {
            if ($job->command === $command) {
                $this->information('Scheduler job already exists');
                return;
            }
        }

        $this->information('Creating scheduler job');

        $this->forge->createJob($server->id, [
            'command' => $command,
            'frequency' => 'minutely',
            'user' => 'forge',
        ]);
    }

    protected function buildScheduledJobCommand(): string
    {
        return sprintf("php /home/forge/%s/artisan schedule:run", $this->generateSiteDomain($this->config->branchName, $this->config->domainName));
    }

    protected function maybeCreateDatabase(Server $server, Site $site)
    {
        $name = $this->getDatabaseName($this->config->branchName);

        foreach ($this->forge->databases($server->id) as $database) {
            if ($database->name === $name) {
                $this->information('Database already exists.');

                return;
            }
        }

        $this->information('Creating database');

        $this->forge->createDatabase($server->id, [
            'name' => $this->getDatabaseName($this->config->branchName),
            'user' => $this->getDatabaseUserName($this->config->branchName),
            'password' => $this->getDatabasePassword(),
        ]);

        $this->information('Updating site environment variables');

        $env = $this->forge->siteEnvironmentFile($server->id, $site->id);
        $env = preg_replace([
            "/DB_DATABASE=.*/",
            "/DB_USERNAME=.*/",
            "/DB_PASSWORD=.*/",
        ], [
            "DB_DATABASE={$this->getDatabaseName($this->config->branchName)}",
            "DB_USERNAME={$this->getDatabaseUserName($this->config->branchName)}",
            "DB_PASSWORD={$this->getDatabasePassword()}"
        ], $env);

        $this->forge->updateSiteEnvironmentFile($server->id, $site->id, $env);
    }

    protected function maybeOutput(string $key, string $value): void
    {
        if ($this->config->ciOutputRequired) {
            // @TODO: Support different providers, (currently outputing in GitHub format)
            $this->line("::set-output name=forge_previewer_{$key}::$value");
        }
    }

    protected function findOrCreateSite(Server $server): Site
    {
        $sites = $this->forge->sites($server->id);
        $domain = $this->generateSiteDomain($this->config->branchName, $this->config->domainName);

        $this->maybeOutput('domain', $domain);

        foreach ($sites as $site) {
            if ($site->name === $domain) {
                $this->information('Found existing site.');

                return $site;
            }
        }

        $this->information('Creating site with domain ' . $domain);

        $data = [
            'domain' => $domain,
            'project_type' => 'php',
            'php_version' => $this->config->phpVersion,
            'directory' => '/public'
        ];

        if ($this->config->isolateRequired) {
            $this->information('Enabling site isolation');

            $data['isolation'] = true;
            $data['username'] = str($this->config->branchName)->slug();
        }

        $site = $this->forge->createSite($server->id, $data);

        $this->information('Installing Git repository');

        $site->installGitRepository([
            'provider' => $this->config->providerName,
            'repository' => $this->config->repositoryName,
            'branch' => $this->config->branchName,
            'composer' => true,
        ]);

        if ($this->config->quickDeployRequired) {
            $this->information('Enabling quick deploy');

            $site->enableQuickDeploy();
        }

        $this->information('Generating SSL certificate');

        $this->forge->obtainLetsEncryptCertificate($server->id, $site->id, [
            'domains' => [$this->generateSiteDomain($this->config->branchName, $this->config->domainName)],
        ]);

        return $site;
    }

    protected function resolveConfiguration(): void
    {
        try {
            $this->config = new DeployConfiguration($this->options());
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }
    }
}
