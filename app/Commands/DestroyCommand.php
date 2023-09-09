<?php

namespace App\Commands;

use App\Commands\Concerns\DestroyConfiguration;
use Exception;
use Laravel\Forge\Forge;
use App\Commands\Concerns\HandlesOutput;
use LaravelZero\Framework\Commands\Command;
use App\Commands\Concerns\GeneratesSiteInfo;
use App\Commands\Concerns\GeneratesDatabaseInfo;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;
use Throwable;

class DestroyCommand extends Command
{
    use HandlesOutput;
    use GeneratesDatabaseInfo;
    use GeneratesSiteInfo;

    protected $signature = 'destroy
        {--token=  : The Forge API token.}
        {--server= : The ID of the target server.}
        {--repo= : The name of the repository being deployed.}
        {--branch= : The name of the branch being deployed.}
        {--domain= : The domain you\'d like to use for deployments.}';

    protected $description = 'Destroy a previously created preview site.';

    protected Forge $forge;

    protected DestroyConfiguration $config;

    public function handle(Forge $forge)
    {
        $this->resolveConfiguration();

        $this->forge = $forge->setApiKey($this->config->forgeToken);

        try {
            $server = $forge->server($this->config->forgeServer);
        } catch (Exception $_) {
            return $this->fail("Failed to find server.");
        }

        $generatedSiteDomain = $this->generateSiteDomain($this->config->branchName, $this->config->domainName);

        $site = $this->findSite($server, $generatedSiteDomain);

        if (! $site) {
            return $this->fail('Failed to find site.');
        }

        $this->information('Found site.');

        foreach ($forge->certificates($server->id, $site->id) as $certificate) {
            if ($certificate->domain === $generatedSiteDomain) {
                $this->information('Deleting SSL certificate.');
                $certificate->delete();
            }
        }

        foreach ($forge->jobs($server->id) as $job) {
            if ($job->command === sprintf("php /home/forge/%s/artisan schedule:run", $generatedSiteDomain)) {
                $this->information('Removing scheduled command.');
                $job->delete();
            }
        }

        foreach ($forge->databases($server->id) as $database) {
            if ($database->name === $this->getDatabaseName($this->config->branchName)) {
                $this->information('Removing database.');
                $database->delete();
            }
        }

        foreach ($forge->databaseUsers($server->id) as $databaseUser) {
            if ($databaseUser->name === $this->getDatabaseUserName($this->config->branchName)) {
                $this->information('Removing database user.');
                $database->delete();
            }
        }

        $this->information('Deleting site.');

        $site->delete();

        $this->success('All done!');
    }

    protected function findSite(Server $server, string $domain): ?Site
    {
        $sites = $this->forge->sites($server->id);

        foreach ($sites as $site) {
            if ($site->name === $domain) {
                return $site;
            }
        }

        return null;
    }

    protected function resolveConfiguration(): void
    {
        try {
            $this->config = new DestroyConfiguration($this->options());
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }
    }
}
