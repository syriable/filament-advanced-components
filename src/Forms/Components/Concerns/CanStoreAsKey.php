<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components\Concerns;

use Closure;

trait CanStoreAsKey
{
    protected bool | Closure | null $storeAsKey = null;

    public function storeAsKey(bool | Closure | null $condition = true): static
    {
        $this->storeAsKey = $condition;

        return $this;
    }

    public function shouldStoreAsKey(): bool
    {
        return $this->evaluate($this->storeAsKey) || config('filament-advanced-components.components.palette.store_as_key', false);
    }
}