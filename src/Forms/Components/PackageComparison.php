<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns\HasComparisonAppearance;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns\HasPackages;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns\HasRows;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\StateNormalizer;

/**
 * A Fiverr-style package comparison editor as a native Filament form field.
 *
 * The user visually builds a comparison table: columns are packages
 * (add / remove / rename / drag to reorder), rows are features with a type
 * (boolean, text, number, price, select, radio, textarea, description,
 * delivery, footer, or any user-defined {@see RowTypes\RowType}), and every
 * cell is the value of one feature for one package. The whole table is a
 * single JSON state:
 *
 * ```json
 * {
 *     "packages": [{"id": "…", "title": "Starter", "meta": {}}],
 *     "rows": [{"id": "…", "label": "Responsive", "type": "boolean",
 *               "config": {}, "values": {"package-id": true}}]
 * }
 * ```
 *
 * ```php
 * PackageComparison::make('packages')
 *     ->minPackages(1)
 *     ->maxPackages(6)
 *     ->defaultPackages(3)
 *     ->allowPackageReordering()
 *     ->allowFeatureReordering()
 *     ->allowedRowTypes([BooleanRow::class, NumberRow::class, PriceRow::class])
 *     ->defaultRows([
 *         ['label' => 'Description', 'type' => 'description'],
 *         ['label' => 'Price', 'type' => 'price'],
 *     ])
 *     ->collapsible()
 *     ->live()
 * ```
 *
 * All interactivity happens client-side in Alpine against the entangled
 * state, so no Livewire request fires until the form (or a `->live()` sync)
 * does — and the server re-normalizes the payload on dehydration, so nothing
 * the browser sends is trusted.
 */
class PackageComparison extends Field
{
    use HasComparisonAppearance;
    use HasExtraAlpineAttributes;
    use HasPackages;
    use HasRows;

    protected string $view = 'filament-advanced-components::components.package-comparison';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(static fn (PackageComparison $component): array => $component->normalizeState(null));

        $this->afterStateHydrated(static function (PackageComparison $component, mixed $state): void {
            $component->state($component->normalizeState($state));
        });

        $this->dehydrateStateUsing(static fn (PackageComparison $component, mixed $state): array => $component->normalizeState($state));

        $this->rule(static fn (PackageComparison $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $count = is_array($value) && is_array($value['packages'] ?? null)
                ? count($value['packages'])
                : 0;

            $min = $component->getMinPackages();
            $max = $component->getMaxPackages();

            if ($count < $min) {
                $fail(trans_choice('filament-advanced-components::package-comparison.validation.min_packages', $min, ['min' => $min]));
            }

            if (($max !== null) && ($count > $max)) {
                $fail(trans_choice('filament-advanced-components::package-comparison.validation.max_packages', $max, ['max' => $max]));
            }
        });
    }

    /**
     * Coerce an untrusted state into the canonical JSON shape, seeding the
     * configured default packages and rows when the state is empty.
     *
     * @return array{packages: array<array<string, mixed>>, rows: array<array<string, mixed>>}
     */
    public function normalizeState(mixed $state): array
    {
        return (new StateNormalizer($this))->normalize($state);
    }
}
