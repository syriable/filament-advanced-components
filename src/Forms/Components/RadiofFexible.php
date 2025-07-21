<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Closure;
use Filament\Forms\Components\ToggleButtons;

class RadiofFexible extends ToggleButtons
{
    protected string $view = 'filament-advanced-components::forms.components.radio-fexible';

    public const GROUPED_VIEW = 'filament-advanced-components::forms.components.radio-fexible';

    protected bool|Closure $isFexible = false;

    protected bool | Closure $isNumeric = false;

    protected string | Closure | null $type = null;


    public function numeric(bool | Closure $condition = true): static
    {
        $this->isNumeric = $condition;

        $this->rule('numeric', $condition);

        return $this;
    }

    public function isNumeric(): bool
    {
        return (bool) $this->evaluate($this->isNumeric);
    }

    public function type(string | Closure | null $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        if ($type = $this->evaluate($this->type)) {
            return $type;
        }  elseif ($this->isNumeric()) {
            return 'number';
        } 

        return 'text';
    }

    public function fexible(bool|Closure $condition = true): static
    {
        $this->isFexible = $condition;

        return $this;
    }

    public function isFexible(): bool
    {
        if (!empty($this->getStateCasts())) {
            throw new \InvalidArgumentException('Cannot use flexible mode with enum state casts');
        }
        return (bool) $this->evaluate($this->isFexible);
    }

    /**
     * @return ?array<string>
     */
    public function getInValidationRuleValues(): ?array
    {
        $values = collect(parent::getInValidationRuleValues())
            ->reject(fn ($value) => $this->getState() == $value)
            ->merge($this->getState())
            ->toArray();

        if ($values !== null) {
            return $values;
        }

        return array_keys(collect($this->getEnabledOptions())
            ->reject(fn ($value) => $this->getState() == $value)
            ->merge($this->getState())
            ->toArray());
    }
}
