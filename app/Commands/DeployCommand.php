<?php

namespace App\Commands;

use App\Commands\Concerns\GeneratesDatabaseInfo;
use App\Commands\Concerns\GeneratesSiteInfo;
use Exception;
use Laravel\Forge\Forge;
use Illuminate\Support\Str;
use Laravel\Forge\Resources\Site;
use Laravel\Forge\Resources\Server;
use App\Commands\Concerns\HandlesOutput;
use App\Commands\Concerns\InteractsWithEnv;
use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{
    use HandlesOutput;
    use InteractsWithEnv;
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
        {--no-db : Avoid creating a database.}
        {--timeout= : Set the timeout (in seconds) used for Forge API requests, defaults to 30 seconds.}';

    protected $description = 'Deploy a branch / pull request to Laravel Forge.';

    protected Forge $forge;

    public function handle(Forge $forge)
    {
        $this->forge = $forge->setApiKey($this->getForgeToken());

        if ($timeout = $this->option('timeout')) {
            $this->forge->setTimeout($timeout);
        }

        try {
            $server = $forge->server($this->getForgeServer());
        } catch (Exception $_) {
            return $this->fail("Failed to find server.");
        }

        $site = $this->findOrCreateSite($server);

        if (! $this->option('no-db')) {
            $this->maybeCreateDatabase($server, $site);
        }

        if ($this->option('edit-env')) {
            $this->information('Updating environment variables');

            $envSource = $forge->siteEnvironmentFile($server->id, $site->id);

            foreach ($this->option('edit-env') as $env) {
                [$key, $value] = explode(':', $env, 2);

                $envSource = $this->updateEnvVariable($key, $value, $envSource);
            }

            $forge->updateSiteEnvironmentFile($server->id, $site->id, $envSource);
        }

        if (! $this->option('no-deploy')) {
            $this->information('Deploying');

            $site->deploySite();
        }

        foreach ($this->option('command') as $i => $command) {
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
        if (! $this->option('scheduler')) {
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
        return sprintf("php /home/forge/%s/artisan schedule:run", $this->generateSiteDomain());
    }

    protected function maybeCreateDatabase(Server $server, Site $site)
    {
        $name = $this->getDatabaseName();

        foreach ($this->forge->databases($server->id) as $database) {
            if ($database->name === $name) {
                $this->information('Database already exists.');

                return;
            }
        }

        $this->information('Creating database');

        $this->forge->createDatabase($server->id, [
            'name' => $this->getDatabaseName(),
            'user' => $this->getDatabaseUserName(),
            'password' => $this->getDatabasePassword(),
        ]);

        $this->information('Updating site environment variables');

        $env = $this->forge->siteEnvironmentFile($server->id, $site->id);
        $env = preg_replace([
            "/DB_DATABASE=.*/",
            "/DB_USERNAME=.*/",
            "/DB_PASSWORD=.*/",
        ], [
            "DB_DATABASE={$this->getDatabaseName()}",
            "DB_USERNAME={$this->getDatabaseUserName()}",
            "DB_PASSWORD={$this->getDatabasePassword()}"
        ], $env);

        $this->forge->updateSiteEnvironmentFile($server->id, $site->id, $env);
    }

    protected function maybeOutput(string $key, string $value): void
    {
        if ($this->option('ci')) {
            // @TODO: Support different providers, (currently outputing in GitHub format)
            $this->line("::set-output name=forge_previewer_{$key}::$value");
        }
    }

    protected function findOrCreateSite(Server $server): Site
    {
        $sites = $this->forge->sites($server->id);
        $domain = $this->generateSiteDomain();

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
            'php_version' => $this->option('php-version'),
            'directory' => '/public'
        ];

        if ($this->option('isolate')) {
            $this->information('Enabling site isolation');

            $data['isolation'] = true;
            $data['username'] = str($this->getBranchName())->slug();
        }

        $site = $this->forge->createSite($server->id, $data);

        $this->information('Installing Git repository');

        $site->installGitRepository([
            'provider' => $this->option('provider'),
            'repository' => $this->getRepoName(),
            'branch' => $this->getBranchName(),
            'composer' => true,
        ]);

        if (! $this->option('no-quick-deploy')) {
            $this->information('Enabling quick deploy');

            $site->enableQuickDeploy();
        }

        $this->information('Generating SSL certificate');

        $this->forge->obtainLetsEncryptCertificate($server->id, $site->id, [
            'domains' => [$this->generateSiteDomain()],
        ]);

        return $site;
    }
}
