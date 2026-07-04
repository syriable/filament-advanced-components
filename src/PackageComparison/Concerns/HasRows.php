<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\BooleanRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\DeliveryRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\DescriptionRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\FooterRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\NumberRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\PriceRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\RadioRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\RowType;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\SelectRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\TextareaRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\TextRow;

/**
 * Configuration for the feature axis (the rows) of a `PackageComparison`
 * field: which row types the user may add, the rows a fresh field starts
 * with, and what the user may do to them.
 */
trait HasRows
{
    /**
     * @var array<class-string<RowType> | RowType> | Closure | null
     */
    protected array | Closure | null $allowedRowTypes = null;

    /**
     * @var array<array<string, mixed>> | Closure
     */
    protected array | Closure $defaultRows = [];

    protected bool | Closure $isFeatureReorderingAllowed = true;

    protected bool | Closure $areFeaturesAddable = true;

    protected bool | Closure $areFeaturesDeletable = true;

    /**
     * @param  array<class-string<RowType> | RowType> | Closure  $types
     */
    public function allowedRowTypes(array | Closure $types): static
    {
        $this->allowedRowTypes = $types;

        return $this;
    }

    /**
     * The feature rows a fresh (empty-state) field is seeded with. Each entry
     * is an array with `label`, `type` (a row type machine name), and an
     * optional `config`:
     *
     * ```php
     * ->defaultRows([
     *     ['label' => 'Description', 'type' => 'description'],
     *     ['label' => 'Price', 'type' => 'price', 'config' => ['currency' => 'EUR']],
     * ])
     * ```
     *
     * @param  array<array<string, mixed>> | Closure  $rows
     */
    public function defaultRows(array | Closure $rows): static
    {
        $this->defaultRows = $rows;

        return $this;
    }

    public function allowFeatureReordering(bool | Closure $condition = true): static
    {
        $this->isFeatureReorderingAllowed = $condition;

        return $this;
    }

    public function addableFeatures(bool | Closure $condition = true): static
    {
        $this->areFeaturesAddable = $condition;

        return $this;
    }

    public function deletableFeatures(bool | Closure $condition = true): static
    {
        $this->areFeaturesDeletable = $condition;

        return $this;
    }

    /**
     * The allowed row types as instances, keyed by machine name.
     *
     * @return array<string, RowType>
     */
    public function getRowTypes(): array
    {
        $types = $this->evaluate($this->allowedRowTypes) ?? static::getDefaultRowTypes();

        $instances = [];

        foreach ($types as $type) {
            $instance = $type instanceof RowType ? $type : $type::make();

            $instances[$instance->getName()] = $instance;
        }

        return $instances;
    }

    public function getRowType(string $name): ?RowType
    {
        return $this->getRowTypes()[$name] ?? null;
    }

    /**
     * The serialized row type descriptors the Alpine component receives.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRowTypeDescriptors(): array
    {
        return array_map(
            static fn (RowType $type): array => $type->toDescriptor(),
            $this->getRowTypes(),
        );
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getDefaultRows(): array
    {
        return $this->evaluate($this->defaultRows) ?? [];
    }

    public function isFeatureReorderingAllowed(): bool
    {
        return (bool) $this->evaluate($this->isFeatureReorderingAllowed) && (! $this->isDisabled());
    }

    public function areFeaturesAddable(): bool
    {
        return (bool) $this->evaluate($this->areFeaturesAddable) && (! $this->isDisabled());
    }

    public function areFeaturesDeletable(): bool
    {
        return (bool) $this->evaluate($this->areFeaturesDeletable) && (! $this->isDisabled());
    }

    /**
     * @return array<class-string<RowType>>
     */
    public static function getDefaultRowTypes(): array
    {
        return [
            BooleanRow::class,
            TextRow::class,
            NumberRow::class,
            PriceRow::class,
            SelectRow::class,
            RadioRow::class,
            TextareaRow::class,
            DescriptionRow::class,
            DeliveryRow::class,
            FooterRow::class,
        ];
    }
}
