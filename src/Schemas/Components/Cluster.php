<?php

namespace Syriable\FilamentAdvancedComponents\Schemas\Components;

use Filament\Schemas\Components\FusedGroup;

class Cluster extends FusedGroup
{

    protected string $view = 'filament-advanced-components::schemas.components.cluster';

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(true);

        $num = count($this->getDefaultChildComponents());
        $this->columns([
            'xl' => $num,
            'lg' => $num <= 4 ? $num : 3,
            'md' => 2,
            'sm' => 1,
        ]);
    }
}
