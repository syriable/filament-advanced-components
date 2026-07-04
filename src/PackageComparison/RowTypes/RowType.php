<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * The Strategy contract behind every row of a `PackageComparison` table.
 *
 * A row type describes what one kind of feature row *is*: its machine name,
 * the default value a cell starts with, how untrusted cell values are coerced
 * back into shape on the server, and which Blade template renders its cells.
 *
 * The editor is rendered client-side by Alpine, so a row type contributes its
 * cell markup as a `<template x-if="row.type === '...'">` block — stamped once
 * per allowed type by the field view, then cloned by Alpine for every
 * row × package intersection. Adding a new row type therefore never touches
 * the field, the normalizer, or any existing type:
 *
 * ```php
 * class RatingRow extends RowType
 * {
 *     public function getName(): string
 *     {
 *         return 'rating';
 *     }
 *
 *     public function getDefaultValue(): mixed
 *     {
 *         return 0;
 *     }
 *
 *     public function normalizeValue(mixed $value, array $config): mixed
 *     {
 *         return is_numeric($value) ? max(0, min(5, (int) $value)) : 0;
 *     }
 *
 *     public function getCellView(): string
 *     {
 *         return 'components.rating-cell'; // any view namespace works
 *     }
 * }
 *
 * PackageComparison::make('packages')->allowedRowTypes([RatingRow::class, ...]);
 * ```
 */
abstract class RowType
{
    // Final so `make()` / `new static` stays safe for every subclass; row
    // types are configured through overridden getters, not constructor args.
    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    /**
     * The machine name stored in the JSON state (`rows[].type`).
     */
    abstract public function getName(): string;

    /**
     * The value a cell of this type starts with when a row or package is added.
     */
    abstract public function getDefaultValue(): mixed;

    /**
     * Coerce an untrusted cell value coming back from the browser.
     *
     * @param  array<string, mixed>  $config  the row's normalized per-row config
     */
    abstract public function normalizeValue(mixed $value, array $config): mixed;

    /**
     * The Blade view containing this type's `<template x-if>` cell block.
     */
    abstract public function getCellView(): string;

    /**
     * The human label shown in the "add feature" type picker. Resolved from
     * the package translations when a key exists, so built-in types localize
     * automatically while user-land types fall back to a headline of the name.
     */
    public function getLabel(): string
    {
        $key = "filament-advanced-components::package-comparison.types.{$this->getName()}";

        return Lang::has($key) ? __($key) : Str::headline($this->getName());
    }

    /**
     * The Heroicon shown next to the label in the type picker.
     */
    public function getIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    /**
     * The per-row settings this type understands (e.g. `options` for selects,
     * `currency` for prices). Unknown keys sent by the browser are dropped.
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * Coerce an untrusted per-row config: only keys declared in
     * {@see getDefaultConfig()} survive, everything else is discarded.
     *
     * @return array<string, mixed>
     */
    public function normalizeConfig(mixed $config): array
    {
        $defaults = $this->getDefaultConfig();

        if (! is_array($config)) {
            return $defaults;
        }

        return [...$defaults, ...array_intersect_key($config, $defaults)];
    }

    /**
     * An optional Blade view rendered inside the row's settings popover
     * (e.g. an options editor for selects). Return `null` for none.
     */
    public function getSettingsView(): ?string
    {
        return null;
    }

    /**
     * The serialized shape the Alpine component receives, used client-side to
     * seed new rows, packages, and cells without a server round-trip.
     *
     * @return array<string, mixed>
     */
    public function toDescriptor(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'default' => $this->getDefaultValue(),
            'config' => $this->getDefaultConfig(),
            'hasSettings' => $this->getSettingsView() !== null,
        ];
    }
}
