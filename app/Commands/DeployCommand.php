<?php

namespace App\Commands;

use Exception;
use Laravel\Forge\Forge;
use Illuminate\Support\Str;
use Laravel\Forge\Resources\Site;
use App\Commands\Concerns\CanFail;
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
        {--C|composer : Install Composer dependencies on the Forge site.}';

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
        ]);

        $this->information('Installing Git repository');

        $site->installGitRepository([
            'provider' => $this->option('provider'),
            'repository' => $this->getRepoName(),
            'branch' => $this->getBranchName(),
            'composer' => $this->option('composer'),
        ]);

        $this->information('Enabling quick deploy');

        $site->enableQuickDeploy();

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
