<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasExtra;
use Filament\Forms\Components\CheckboxList as Base;

class CheckboxList extends Base
{
    use HasExtra;

    protected string $view = 'filament-advanced-components::forms.components.checkbox-list';

    protected function setUp(): void
    {
        parent::setUp();
    }
}
