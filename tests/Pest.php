<?php

use Filament\Tables\Columns\Column;
use Livewire\Livewire;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\ColumnHostComponent;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\Contact;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Mount a column into a real (record-backed) table context, so state,
 * closures, and rendering behave exactly as they would inside a panel.
 *
 * @param  array<string, mixed>  $recordAttributes
 */
function mountColumn(Column $column, array $recordAttributes = []): Column
{
    $livewire = Livewire::test(ColumnHostComponent::class)->instance();

    $column->table($livewire->getTable());
    $column->record((new Contact)->forceFill(['id' => 1, ...$recordAttributes]));

    return $column;
}

/**
 * Render a column's cell HTML inside the mounted table context.
 *
 * @param  array<string, mixed>  $recordAttributes
 */
function renderCell(Column $column, array $recordAttributes = []): string
{
    return mountColumn($column, $recordAttributes)->toEmbeddedHtml();
}
