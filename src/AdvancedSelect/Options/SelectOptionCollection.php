<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options;

use BackedEnum;
use Filament\Support\Components\ViewComponent;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * A typed collection of {@see SelectOption} definitions.
 *
 * It is the single normalization point that turns the many shapes an option
 * list may take — a `value => label` map, an enum, a list of `SelectOption`
 * objects, or a mix — into one uniform, render-ready structure.
 *
 * @extends Collection<int, SelectOption>
 */
class SelectOptionCollection extends Collection
{
    /**
     * @param  array<int, SelectOption>  $items
     */
    final public function __construct($items = [])
    {
        parent::__construct($items);
    }

    /**
     * Normalize an arbitrary option list into a collection of
     * {@see SelectOption} objects.
     *
     * Accepts a list of `SelectOption` objects, a `value => label`
     * associative array, a `groupLabel => [value => label]` grouped array, or
     * any mixture of these. Plain scalars become label-only options.
     *
     * @param  iterable<mixed, mixed> | Arrayable<array-key, mixed>  $options
     */
    public static function normalize(iterable | Arrayable $options): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $normalized = [];

        foreach ($options as $key => $value) {
            if ($value instanceof SelectOption) {
                $normalized[] = $value;

                continue;
            }

            // A grouped array: `groupLabel => [value => label]`.
            if (is_array($value) || $value instanceof Arrayable) {
                foreach (static::normalize($value) as $groupedOption) {
                    $normalized[] = $groupedOption->group((string) $key);
                }

                continue;
            }

            // A plain `value => label` pair. A list (integer keys) with scalar
            // values is treated as `label => label`, matching how Filament
            // renders a sequential options array.
            $normalized[] = (is_int($key) && ! ($value instanceof BackedEnum)
                ? SelectOption::make($value, $value)
                : SelectOption::make($key, $value))->markImplicit();
        }

        return new static($normalized);
    }

    /**
     * Resolve every option against the owning component, dropping the ones
     * hidden for this render.
     *
     * @return array<OptionViewModel>
     */
    public function resolveFor(ViewComponent $component): array
    {
        $viewModels = [];

        foreach ($this->all() as $option) {
            if ($viewModel = $option->resolve($component)) {
                $viewModels[] = $viewModel;
            }
        }

        return $viewModels;
    }
}
