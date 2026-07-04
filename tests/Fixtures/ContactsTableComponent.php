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
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

/**
 * A minimal Livewire component owning a table, so AdvancedTextColumn can be
 * exercised through Filament's real rendering lifecycle in tests.
 */
class ContactsTableComponent extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Contact::query())
            ->columns([
                AdvancedTextColumn::make('name')
                    ->bold(fn (Contact $record): bool => (bool) $record->is_admin)
                    ->characterCount(),
                AdvancedTextColumn::make('email')
                    ->mailable(),
                AdvancedTextColumn::make('phone')
                    ->masked()
                    ->maskIndex(3)
                    ->maskCharacter('*'),
            ]);
    }

    public function render(): string
    {
        return '<div>{{ $this->table }}</div>';
    }
}
