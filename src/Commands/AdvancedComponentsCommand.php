<?php

namespace Syriable\Filament\Plugins\AdvancedComponents\Commands;

use Illuminate\Console\Command;

class AdvancedComponentsCommand extends Command
{
    public $signature = 'filament-advanced-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
