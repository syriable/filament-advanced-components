<?php

namespace Syriable\FilamentAdvancedComponents\Schemas\Components;

use Filament\Schemas\Components\Section;

class Accordion extends Section
{

    protected string $view = 'filament-advanced-components::schemas.components.accordion';

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(true);
    }
}