<?php

namespace Syriable\Filament\Plugins\AdvancedComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Syriable\Filament\Plugins\AdvancedComponents\AdvancedComponents
 */
class AdvancedComponents extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Syriable\Filament\Plugins\AdvancedComponents\AdvancedComponents::class;
    }
}
