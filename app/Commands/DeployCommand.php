<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{
    protected $signature = 'deploy';

    protected $description = 'Deploy a branch / pull request to Laravel Forge.';

    public function handle()
    {
        //
    }
}
