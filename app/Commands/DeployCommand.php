<?php

namespace App\Commands;

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

    protected $signature = 'deploy
        {--T|token=  : The Forge API token.}
        {--S|server= : The ID of the target server.}
        {--G|provider=github : The Git provider.}
        {--R|repo= : The name of the repository being deployed.}
        {--B|branch= : The name of the branch being deployed.}
        {--D|domain= : The domain you\'d like to use for deployments.}
        {--P|php-version=php81 : The version of PHP the site should use, e.g. php81, php80, ...}
        {--C|command=* : A command you would like to execute on the site, e.g. php artisan db:seed.}';

    protected $description = 'Deploy a branch / pull request to Laravel Forge.';

    protected Forge $forge;

    public function handle(Forge $forge)
    {
        $this->forge = $forge->setApiKey($this->getForgeToken());

        try {
            $server = $forge->server($this->getForgeServer());
        } catch (Exception $_) {
            return $this->fail("Failed to find server.");
        }

        $site = $this->findOrCreateSite($server);

        $this->maybeCreateDatabase($server, $site);

        foreach ($this->option('command') as $i => $command) {
            if ($i === 0) {
                $this->information('Executing site command(s)');
            }

            $forge->executeSiteCommand($server->id, $site->id, [
                'command' => $command,
            ]);
        }
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

    protected function findOrCreateSite(Server $server): Site
    {
        $sites = $this->forge->sites($server->id);
        $domain = $this->generateSiteDomain();

        foreach ($sites as $site) {
            if ($site->name === $domain) {
                $this->information('Found existing site.');

                return $site;
            }
        }

        $this->information('Creating site with domain ' . $domain);

        $site = $this->forge->createSite($server->id, [
            'domain' => $domain,
            'project_type' => 'php',
            'php_version' => $this->option('php-version'),
            'directory' => '/public'
        ]);

        $this->information('Installing Git repository');

        $site->installGitRepository([
            'provider' => $this->option('provider'),
            'repository' => $this->getRepoName(),
            'branch' => $this->getBranchName(),
            'composer' => true,
        ]);

        $this->information('Enabling quick deploy');

        $site->enableQuickDeploy();

        $this->information('Deploying');

        $site->deploySite();

        $this->information('Generating SSL certificate');

        $this->forge->obtainLetsEncryptCertificate($server->id, $site->id, [
            'domains' => [$this->generateSiteDomain()],
        ]);

        return $site;
    }

    protected function generateSiteDomain(): string
    {
        return str($this->getBranchName())->slug()->append('.', $this->getDomainName())->toString();
    }

    protected function getDatabaseUserName(): string
    {
        return $this->getDatabaseName();
    }

    protected function getDatabasePassword(): string
    {
        static $password;

        return $password ??= Str::random(16);
    }

    protected function getDatabaseName(): string
    {
        return str($this->getBranchName())->slug('_')->limit(64)->toString();
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
