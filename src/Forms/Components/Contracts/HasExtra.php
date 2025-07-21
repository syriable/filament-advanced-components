<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components\Contracts;

use Illuminate\Contracts\Support\Htmlable;

interface HasExtra
{
    public function getExtra(): string | Htmlable | null;
}