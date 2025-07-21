<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\CheckboxList;

class CheckboxTable extends CheckboxList
{
    use HasExtra;

    protected string $view = 'filament-advanced-components::forms.components.checkbox-table';
    
    protected function setUp(): void
    {
        parent::setUp();
    }
}
