<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison;

use Illuminate\Support\Str;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PackageComparison;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\RowType;

/**
 * The single place that knows the shape of a `PackageComparison` JSON state.
 *
 * It runs on hydration *and* dehydration, so the browser payload is never
 * trusted: UUIDs are guaranteed (and de-duplicated), `values` maps are keyed
 * strictly by existing package ids, orphaned values from deleted packages are
 * pruned, rows of unknown types are dropped, and every cell value and per-row
 * config is coerced through its row type's strategy.
 *
 * A `null` / empty state is seeded: `defaultPackages()` lettered packages
 * ("Package A", …) and the field's `defaultRows()`.
 */
class StateNormalizer
{
    public function __construct(
        protected PackageComparison $field,
    ) {}

    /**
     * @return array{packages: array<array<string, mixed>>, rows: array<array<string, mixed>>}
     */
    public function normalize(mixed $state): array
    {
        $state = is_array($state) ? $state : [];

        $packages = $this->normalizePackages($state['packages'] ?? null);

        $rows = array_key_exists('rows', $state)
            ? $this->normalizeRows($state['rows'], $packages)
            : $this->normalizeRows($this->field->getDefaultRows(), $packages);

        return [
            'packages' => $packages,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function normalizePackages(mixed $packages): array
    {
        $packages = is_array($packages) ? array_values(array_filter($packages, is_array(...))) : [];

        if ($packages === []) {
            $packages = array_fill(0, $this->field->getDefaultPackages(), []);
        }

        $normalized = [];
        $seenIds = [];

        foreach ($packages as $index => $package) {
            $id = $package['id'] ?? null;

            if ((! is_string($id)) || ($id === '') || isset($seenIds[$id])) {
                $id = (string) Str::uuid();
            }

            $seenIds[$id] = true;

            $title = is_scalar($package['title'] ?? null) ? trim((string) $package['title']) : '';

            $normalized[] = [
                'id' => $id,
                'title' => ($title !== '') ? $title : static::defaultPackageTitle($index),
                'meta' => is_array($package['meta'] ?? null) ? $package['meta'] : [],
            ];
        }

        // The minimum is a hard invariant, so a short list is padded rather
        // than left for validation to reject with no way out of the error.
        while (count($normalized) < $this->field->getMinPackages()) {
            $normalized[] = [
                'id' => (string) Str::uuid(),
                'title' => static::defaultPackageTitle(count($normalized)),
                'meta' => [],
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<array<string, mixed>>  $packages
     * @return array<array<string, mixed>>
     */
    protected function normalizeRows(mixed $rows, array $packages): array
    {
        $rows = is_array($rows) ? array_values(array_filter($rows, is_array(...))) : [];

        $packageIds = array_column($packages, 'id');

        $normalized = [];
        $seenIds = [];

        foreach ($rows as $row) {
            $type = is_string($row['type'] ?? null) ? $this->field->getRowType($row['type']) : null;

            if (! $type instanceof RowType) {
                continue;
            }

            $id = $row['id'] ?? null;

            if ((! is_string($id)) || ($id === '') || isset($seenIds[$id])) {
                $id = (string) Str::uuid();
            }

            $seenIds[$id] = true;

            $config = $type->normalizeConfig($row['config'] ?? null);

            $rawValues = is_array($row['values'] ?? null) ? $row['values'] : [];

            $values = [];

            foreach ($packageIds as $packageId) {
                $values[$packageId] = $type->normalizeValue(
                    array_key_exists($packageId, $rawValues) ? $rawValues[$packageId] : $type->getDefaultValue(),
                    $config,
                );
            }

            $normalized[] = [
                'id' => $id,
                'label' => is_scalar($row['label'] ?? null) ? trim((string) $row['label']) : '',
                'type' => $type->getName(),
                'config' => $config,
                'values' => $values,
            ];
        }

        return $normalized;
    }

    /**
     * "Package A" … "Package Z", "Package AA", … — mirrored client-side by
     * the Alpine component so packages added in the browser match.
     */
    public static function defaultPackageTitle(int $index): string
    {
        $letters = '';

        do {
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return __('filament-advanced-components::package-comparison.package_default_title', ['letter' => $letters]);
    }
}
