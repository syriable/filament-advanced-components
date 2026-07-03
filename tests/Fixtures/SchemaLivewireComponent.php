<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

/**
 * A minimal Livewire component that can own a schema, so infolist entries
 * can be rendered in isolation inside tests.
 */
class SchemaLivewireComponent extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render(): string
    {
        return '<div></div>';
    }
}
