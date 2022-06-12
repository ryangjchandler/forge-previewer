<?php

namespace App\Commands\Concerns;

use function Termwind\render;

trait HandlesOutput
{
    protected function fail(string $message): int
    {
        render(sprintf(<<<'html'
            <div class="font-bold">
                <span class="bg-red px-2 text-white mr-1">
                    ERROR
                </span>
                %s
            </div>
        html, trim($message)));

        return 1;
    }

    protected function information(string $message)
    {
        render(sprintf(<<<'html'
            <div class="font-bold">
                <span class="bg-blue px-2 text-white mr-1">
                    INFO
                </span>
                %s
            </div>
        html, trim($message)));
    }
}
