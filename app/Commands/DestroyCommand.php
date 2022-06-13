<?php

namespace App\Commands;

use Exception;
use Laravel\Forge\Forge;
use App\Commands\Concerns\HandlesOutput;
use App\Commands\Concerns\InteractsWithEnv;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Commands\Concerns\GeneratesSiteInfo;
use App\Commands\Concerns\GeneratesDatabaseInfo;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;

class DestroyCommand extends Command
{
    use HandlesOutput;
    use InteractsWithEnv;
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

    public function handle(Forge $forge)
    {
        $this->forge = $forge->setApiKey($this->getForgeToken());

        try {
            $server = $forge->server($this->getForgeServer());
        } catch (Exception $_) {
            return $this->fail("Failed to find server.");
        }

        $site = $this->findSite($server);

        if (! $site) {
            return $this->fail('Failed to find site.');
        }

        $this->information('Found site.');

        foreach ($forge->certificates($server->id, $site->id) as $certificate) {
            if ($certificate->domain === $this->generateSiteDomain()) {
                $this->information('Deleting SSL certificate.');
                $certificate->delete();
            }
        }

        foreach ($forge->jobs($server->id) as $job) {
            if ($job->command === sprintf("php /home/forge/%s/artisan schedule:run", $this->generateSiteDomain())) {
                $this->information('Removing scheduled command.');
                $job->delete();
            }
        }

        foreach ($forge->databases($server->id) as $database) {
            if ($database->name === $this->getDatabaseName()) {
                $this->information('Removing database.');
                $database->delete();
            }
        }

        foreach ($forge->databaseUsers($server->id) as $databaseUser) {
            if ($databaseUser->name === $this->getDatabaseUserName()) {
                $this->information('Removing database user.');
                $database->delete();
            }
        }

        $this->information('Deleting site.');

        $site->delete();

        $this->success('All done!');
    }

    protected function findSite(Server $server): ?Site
    {
        $sites = $this->forge->sites($server->id);
        $domain = $this->generateSiteDomain();

        foreach ($sites as $site) {
            if ($site->name === $domain) {
                return $site;
            }
        }

        return null;
    }
}
