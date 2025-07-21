<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\Radio;

class RadioList extends Radio
{
    use HasExtra;

    protected string $view = 'filament-advanced-components::forms.components.radio-list';
}
