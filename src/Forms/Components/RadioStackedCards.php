<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\Radio;

class RadioStackedCards extends Radio
{
    use HasExtra;

    protected string $view = 'filament-advanced-components::forms.components.radio-stacked-cards';

    protected function setUp(): void
    {
        parent::setUp();
    }
}
