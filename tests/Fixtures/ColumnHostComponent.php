<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

/**
 * A bare table owner used to mount individual columns in unit tests. Its
 * render output does not include the table, so no database is required.
 */
class ColumnHostComponent extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table->query(Contact::query());
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
