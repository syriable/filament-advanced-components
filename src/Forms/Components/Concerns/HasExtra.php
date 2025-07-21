<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components\Concerns;

use Syriable\FilamentAdvancedComponents\Forms\Components\Contracts\HasExtra as ContractsHasExtra;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

trait HasExtra
{

     /**
     * @var array<string | Htmlable> | Arrayable | Closure
     */
    protected array | Arrayable | Closure $extras = [];

    /**
     * @param  array<string | Htmlable> | Arrayable | Closure  $extras
     */
    public function extras(array | Arrayable | Closure $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Determine if there are any extras.
     */
    public function hasExtras(): bool
    {
        return !empty($this->getExtras());
    }

    /**
     * Determine if there are any descriptions.
     */
    public function hasDescriptions(): bool
    {
        return !empty($this->getDescriptions());
    }


    /**
     * @param  array-key  $value
     */
    public function hasExtra($value): bool
    {
        return array_key_exists($value, $this->getExtras());
    }

    /**
     * @param  array-key  $value
     */
    public function getExtra($value): string | Htmlable | null
    {
        return $this->getExtras()[$value] ?? null;
    }

    /**
     * @return array<string | Htmlable>
     */
    public function getExtras(): array
    {
        $extras = $this->evaluate($this->extras);

        if ($extras instanceof Arrayable) {
            $extras = $extras->toArray();
        }

        if (
            blank($extras) &&
            filled($enum = $this->getEnum()) &&
            is_a($enum, ContractsHasExtra::class, allow_string: true)
        ) {
            /** @var class-string<ContractsHasExtra & UnitEnum> $enum */
            $extras = array_reduce($enum::cases(), function (array $carry, ContractsHasExtra & UnitEnum $case): array {
                
                if (filled($extra = $case->getExtra())) {
                    $carry[$case->value ?? $case->name] = $extra;
                }

                return $carry;
            }, []);
        }

        return $extras;
    }
}