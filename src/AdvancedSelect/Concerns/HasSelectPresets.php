<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns;

use Closure;
use InvalidArgumentException;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;

/**
 * Component-level presets: name a reusable bundle of configuration once and
 * apply it to any {@see AdvancedSelect}
 * with `preset()`.
 *
 * ```php
 * AdvancedSelect::registerPreset('country', fn (AdvancedSelect $select) => $select
 *     ->searchable()
 *     ->options(Country::options()));
 *
 * AdvancedSelect::make('country')->preset('country');
 * ```
 */
trait HasSelectPresets
{
    /**
     * @var array<string, Closure(static): void>
     */
    protected static array $selectPresets = [];

    /**
     * @param  Closure(static): void  $configure
     */
    public static function registerPreset(string $name, Closure $configure): void
    {
        static::$selectPresets[$name] = $configure;
    }

    public static function hasPreset(string $name): bool
    {
        return isset(static::$selectPresets[$name]);
    }

    public function preset(string $name): static
    {
        $preset = static::$selectPresets[$name] ?? throw new InvalidArgumentException(
            "Select preset [{$name}] is not registered. Register it with " . static::class . '::registerPreset().',
        );

        $preset($this);

        return $this;
    }
}
