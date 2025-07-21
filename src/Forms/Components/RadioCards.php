<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\Radio;

class RadioCards extends Radio
{
use HasExtra;
 
    protected string $view = 'filament-advanced-components::forms.components.radio-cards';

    protected function setUp(): void
    {
        parent::setUp();
    }
}
