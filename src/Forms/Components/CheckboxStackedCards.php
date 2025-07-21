<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\CheckboxList;

class CheckboxStackedCards extends CheckboxList
{
    use HasExtra;

    protected string $view = 'filament-advanced-components::forms.components.checkbox-stacked-cards';

    protected function setUp(): void
    {
        parent::setUp();
    }
}
