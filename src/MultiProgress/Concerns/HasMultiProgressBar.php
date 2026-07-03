<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Concerns;

use BackedEnum;
use Closure;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Segment;
use Syriable\Filament\Plugins\AdvancedComponents\View\Components\MultiProgressSegmentComponent;

use function Filament\Support\generate_icon_html;
use function Filament\Support\get_component_color_classes;

/**
 * The complete configuration API and rendering pipeline of the multi-segment
 * progress bar, shared by the table column (`MultiProgressColumn`) and the
 * infolist entry (`MultiProgressEntry`).
 *
 * The consuming component only has to provide Filament's usual evaluation
 * plumbing (`evaluate()`, `getState()`, `getPlaceholder()`,
 * `getExtraAttributeBag()`) — which both `Filament\Tables\Columns\Column`
 * and `Filament\Infolists\Components\Entry` do — and point its `$view` at
 * the shared `filament-advanced-components::components.multi-progress`
 * Blade view.
 *
 * All heavy lifting (normalization, color resolution, tooltip generation)
 * happens once per render inside {@see getProgressData()}, so the Blade view
 * stays a dumb template — an important property for tables rendering
 * hundreds or thousands of rows.
 */
trait HasMultiProgressBar
{
    /**
     * The semantic colors cycled through when a segment does not declare one.
     *
     * @var array<int, string> | Closure
     */
    protected array | Closure $fallbackColors = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];

    /**
     * @var array<int, Segment | array<string, mixed>> | Arrayable<int, mixed> | Closure | null
     */
    protected array | Arrayable | Closure | null $segments = null;

    protected int | float | Closure | null $total = null;

    protected bool | Closure $shouldShowPercentage = false;

    protected bool | Closure $shouldShowTotal = false;

    protected bool | Closure $shouldShowLegend = false;

    protected bool | Closure $hasSegmentTooltips = true;

    protected bool | Closure $isAnimated = true;

    protected bool | Closure $isStriped = false;

    protected bool | Closure $hasGradient = false;

    protected bool | Closure $hasHoverEffect = false;

    protected bool | Closure $isCompact = false;

    protected bool | Closure $hasSkeleton = false;

    protected Size | string | Closure $size = Size::Medium;

    protected int | string | Closure | null $height = null;

    protected int | Closure $gap = 0;

    protected int | string | Closure | null $borderRadius = null;

    protected int | float | Closure | null $minSegmentWidth = null;

    protected string | Closure | null $valueSuffix = null;

    protected ?Closure $formatValueUsing = null;

    protected ?Closure $formatPercentageUsing = null;

    protected ?Closure $formatSegmentTooltipUsing = null;

    /**
     * Preset track heights for the {@see Size()} variants.
     *
     * @var array<string, string>
     */
    protected static array $sizeHeights = [
        'xs' => '0.25rem',
        'sm' => '0.375rem',
        'md' => '0.625rem',
        'lg' => '1rem',
        'xl' => '1.25rem',
    ];

    /*
    |--------------------------------------------------------------------------
    | Data configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Defines the bar's segments. Accepts a static array or a closure that
     * receives the usual Filament evaluation parameters (`$record`, `$state`,
     * `$livewire`, ...). Each segment is a {@see Segment} object or an array:
     *
     * - `label` (string) — used in tooltips, the legend, and ARIA labels
     * - `value` (int|float) — raw amount or percentage, normalized automatically
     * - `color` (string|array, optional) — semantic name, CSS color, or palette
     * - `tooltip` (string|Htmlable, optional) — overrides the generated tooltip
     * - `icon` (string|BackedEnum, optional) — shown in the tooltip and legend
     * - `badge` (string, optional) — small badge next to the legend entry
     * - `url` (string, optional) — makes the segment clickable
     * - `shouldOpenUrlInNewTab` (bool, optional)
     *
     * When omitted entirely, the component falls back to its own state, so a
     * model accessor returning a segments array also works.
     *
     * @param  array<int, Segment | array<string, mixed>> | Arrayable<int, mixed> | Closure | null  $segments
     */
    public function segments(array | Arrayable | Closure | null $segments): static
    {
        $this->segments = $segments;

        return $this;
    }

    /**
     * The denominator used to convert segment values into percentages. When
     * omitted, the sum of all segment values is used (so values that are
     * already percentages "just work"). When the provided total is larger
     * than the sum, the remainder renders as empty track — handy for showing
     * unfinished work implicitly.
     */
    public function total(int | float | Closure | null $total): static
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Colors cycled through for segments that do not declare their own.
     *
     * @param  array<int, string> | Closure  $colors
     */
    public function fallbackColors(array | Closure $colors): static
    {
        $this->fallbackColors = $colors;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Labels, legend & tooltips
    |--------------------------------------------------------------------------
    */

    /**
     * Shows the overall completion percentage to the right of the bar
     * (the sum of all segment values, relative to the total).
     */
    public function showPercentage(bool | Closure $condition = true): static
    {
        $this->shouldShowPercentage = $condition;

        return $this;
    }

    /**
     * Shows the total count to the right of the bar, after the percentage.
     */
    public function showTotal(bool | Closure $condition = true): static
    {
        $this->shouldShowTotal = $condition;

        return $this;
    }

    /**
     * Renders a legend below the bar: one colored dot per segment with its
     * label, percentage, and optional badge.
     */
    public function showLegend(bool | Closure $condition = true): static
    {
        $this->shouldShowLegend = $condition;

        return $this;
    }

    /**
     * Toggles the per-segment tooltips. Enabled by default; each tooltip
     * shows the segment's label, formatted value, and percentage.
     */
    public function segmentTooltips(bool | Closure $condition = true): static
    {
        $this->hasSegmentTooltips = $condition;

        return $this;
    }

    /**
     * A suffix appended to formatted values, e.g. `keys` → "420 keys".
     */
    public function valueSuffix(string | Closure | null $suffix): static
    {
        $this->valueSuffix = $suffix;

        return $this;
    }

    /**
     * Customizes how raw segment values are formatted in tooltips and the
     * total label. Receives `$state` (the value) plus the usual evaluation
     * parameters, and a `$segment` array when formatting a segment value.
     */
    public function formatValueUsing(?Closure $callback): static
    {
        $this->formatValueUsing = $callback;

        return $this;
    }

    /**
     * Customizes the percentage label. Receives `$state` (a float, e.g.
     * `70.0`) plus the usual evaluation parameters.
     */
    public function formatPercentageUsing(?Closure $callback): static
    {
        $this->formatPercentageUsing = $callback;

        return $this;
    }

    /**
     * Customizes the generated tooltip of every segment. Receives a
     * `$segment` array containing `label`, `value`, `formattedValue` and
     * `percentage` keys, and may return a string or `Htmlable`.
     */
    public function formatSegmentTooltipUsing(?Closure $callback): static
    {
        $this->formatSegmentTooltipUsing = $callback;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Appearance
    |--------------------------------------------------------------------------
    */

    /**
     * A height preset: `xs`, `sm`, `md` (default), `lg` or `xl`.
     */
    public function size(Size | string | Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * An explicit track height, overriding the {@see Size()} preset.
     * Integers are treated as pixels; strings as raw CSS lengths.
     */
    public function height(int | string | Closure | null $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * The spacing between segments, in pixels.
     *
     * Named `segmentGap` (rather than `gap`) because infolist entries
     * inherit Filament's schema-level `gap()` toggle, which controls the
     * layout gap around the component and must keep working.
     */
    public function segmentGap(int | Closure $pixels): static
    {
        $this->gap = $pixels;

        return $this;
    }

    /**
     * The track's border radius. Integers are treated as pixels; strings as
     * raw CSS values. Defaults to fully rounded (pill-shaped).
     */
    public function borderRadius(int | string | Closure | null $radius): static
    {
        $this->borderRadius = $radius;

        return $this;
    }

    /**
     * Squares off the track corners. Shorthand for `borderRadius(0)`.
     */
    public function squared(bool | Closure $condition = true): static
    {
        $this->borderRadius = fn (): ?string => $this->evaluate($condition) ? '0' : null;

        return $this;
    }

    /**
     * Guarantees that any non-zero segment occupies at least this percentage
     * of the track, so tiny slices remain visible. Larger segments shrink
     * proportionally to compensate.
     */
    public function minSegmentWidth(int | float | Closure | null $percentage): static
    {
        $this->minSegmentWidth = $percentage;

        return $this;
    }

    /**
     * Animates segment width changes (e.g. after a Livewire poll or action).
     * Enabled by default.
     */
    public function animated(bool | Closure $condition = true): static
    {
        $this->isAnimated = $condition;

        return $this;
    }

    /**
     * Overlays diagonal stripes on the segments.
     */
    public function striped(bool | Closure $condition = true): static
    {
        $this->isStriped = $condition;

        return $this;
    }

    /**
     * Gives each segment a subtle horizontal gradient.
     */
    public function gradient(bool | Closure $condition = true): static
    {
        $this->hasGradient = $condition;

        return $this;
    }

    /**
     * Brightens segments on hover, signalling their tooltips / links.
     */
    public function hoverEffect(bool | Closure $condition = true): static
    {
        $this->hasHoverEffect = $condition;

        return $this;
    }

    /**
     * Compact mode: a thinner bar, tighter typography, and no legend.
     */
    public function compact(bool | Closure $condition = true): static
    {
        $this->isCompact = $condition;

        return $this;
    }

    /**
     * Renders a pulsing skeleton bar instead of the empty-state placeholder
     * while the segments are empty — useful when data loads asynchronously.
     */
    public function skeleton(bool | Closure $condition = true): static
    {
        $this->hasSkeleton = $condition;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Evaluated getters
    |--------------------------------------------------------------------------
    */

    /**
     * The raw, unnormalized segment definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSegments(): array
    {
        $segments = $this->evaluate($this->segments);

        // Fall back to the component's state, so an Eloquent accessor (or
        // JSON cast attribute) returning a segments array needs no extra
        // setup.
        if ($segments === null) {
            $state = $this->getState();
            $segments = is_array($state) ? $state : [];
        }

        if ($segments instanceof Arrayable) {
            $segments = $segments->toArray();
        }

        return array_values(array_map(
            function (mixed $segment): array {
                if ($segment instanceof Segment) {
                    $segment = $segment->toArray();
                }

                if (! is_array($segment)) {
                    throw new InvalidArgumentException('Each segment of a [' . static::class . '] must be an array or a [' . Segment::class . '] instance, [' . get_debug_type($segment) . '] given.');
                }

                if (! is_numeric($segment['value'] ?? null)) {
                    throw new InvalidArgumentException('Each segment of a [' . static::class . '] must have a numeric [value].');
                }

                $segment['value'] = max(0, $segment['value'] + 0);

                return $segment;
            },
            $segments,
        ));
    }

    public function getTotal(): int | float | null
    {
        return $this->evaluate($this->total);
    }

    public function shouldShowPercentage(): bool
    {
        return (bool) $this->evaluate($this->shouldShowPercentage);
    }

    public function shouldShowTotal(): bool
    {
        return (bool) $this->evaluate($this->shouldShowTotal);
    }

    public function shouldShowLegend(): bool
    {
        return (bool) $this->evaluate($this->shouldShowLegend) && ! $this->isCompact();
    }

    public function hasSegmentTooltips(): bool
    {
        return (bool) $this->evaluate($this->hasSegmentTooltips);
    }

    public function isAnimated(): bool
    {
        return (bool) $this->evaluate($this->isAnimated);
    }

    public function isStriped(): bool
    {
        return (bool) $this->evaluate($this->isStriped);
    }

    public function hasGradient(): bool
    {
        return (bool) $this->evaluate($this->hasGradient);
    }

    public function hasHoverEffect(): bool
    {
        return (bool) $this->evaluate($this->hasHoverEffect);
    }

    public function isCompact(): bool
    {
        return (bool) $this->evaluate($this->isCompact);
    }

    public function hasSkeleton(): bool
    {
        return (bool) $this->evaluate($this->hasSkeleton);
    }

    public function getGap(): int
    {
        return max(0, (int) $this->evaluate($this->gap));
    }

    public function getMinSegmentWidth(): int | float | null
    {
        return $this->evaluate($this->minSegmentWidth);
    }

    /**
     * The track height as a CSS length.
     */
    public function getHeight(): string
    {
        $height = $this->evaluate($this->height);

        if (is_int($height)) {
            return "{$height}px";
        }

        if (filled($height)) {
            return $height;
        }

        if ($this->isCompact()) {
            return static::$sizeHeights['sm'];
        }

        $size = $this->evaluate($this->size);

        if ($size instanceof Size) {
            $size = $size->value;
        }

        return static::$sizeHeights[$size] ?? static::$sizeHeights['md'];
    }

    /**
     * The track border radius as a CSS value.
     */
    public function getBorderRadius(): string
    {
        $radius = $this->evaluate($this->borderRadius);

        if (is_int($radius)) {
            return "{$radius}px";
        }

        return filled($radius) ? $radius : 'calc(infinity * 1px)';
    }

    /*
    |--------------------------------------------------------------------------
    | Processing
    |--------------------------------------------------------------------------
    */

    /**
     * Builds the complete, render-ready payload for the Blade view. Called
     * exactly once per render so no math or color resolution happens in
     * Blade.
     *
     * @return array<string, mixed>
     */
    public function getProgressData(): array
    {
        $segments = $this->getSegments();

        $sum = array_sum(array_column($segments, 'value'));

        // The denominator may never be smaller than the sum of the parts;
        // this transparently handles values that overflow the given total.
        $total = max($this->getTotal() ?? $sum, $sum);

        $isEmpty = ($total <= 0) || ($segments === []);

        $percentages = $isEmpty ? [] : array_map(
            fn (array $segment): float => $segment['value'] / $total * 100,
            $segments,
        );

        $widths = $this->applyMinimumWidths($percentages);

        $processed = [];

        foreach ($segments as $index => $segment) {
            // Zero-value segments are kept for the legend but take no space.
            $percentage = $percentages[$index] ?? 0.0;

            $processed[] = [
                ...$segment,
                'label' => (string) ($segment['label'] ?? ''),
                'percentage' => $percentage,
                'formattedPercentage' => $this->formatPercentage($percentage),
                'width' => round($widths[$index] ?? 0.0, 4),
                'formattedValue' => $this->formatValue($segment['value'], $segment),
                'color' => $this->resolveSegmentColor($segment['color'] ?? null, $index),
                'tooltip' => $this->hasSegmentTooltips()
                    ? $this->resolveSegmentTooltip([...$segment, 'percentage' => $percentage])
                    : null,
                'iconHtml' => $this->resolveIconHtml($segment['icon'] ?? null),
                'badge' => filled($segment['badge'] ?? null) ? (string) $segment['badge'] : null,
                'url' => filled($segment['url'] ?? null) ? (string) $segment['url'] : null,
                'shouldOpenUrlInNewTab' => (bool) ($segment['shouldOpenUrlInNewTab'] ?? false),
            ];
        }

        // The explicit float cast matters: PHP's `/` returns an int when the
        // division is exact, and min() may then return that int on some PHP
        // versions.
        $overallPercentage = $isEmpty ? 0.0 : min((float) ($sum / $total * 100), 100.0);

        return [
            'segments' => $processed,
            'isEmpty' => $isEmpty,
            'percentage' => $overallPercentage,
            'formattedPercentage' => $this->shouldShowPercentage() ? $this->formatPercentage($overallPercentage) : null,
            'formattedTotal' => $this->shouldShowTotal() ? $this->formatValue($total) : null,
            'ariaLabel' => $this->generateAriaLabel($processed, $overallPercentage),
        ];
    }

    /**
     * Enforces {@see minSegmentWidth()}: bumps tiny non-zero slices up to the
     * minimum and shrinks the remaining slices proportionally so the bar
     * never overflows 100%.
     *
     * @param  array<int, float>  $percentages
     * @return array<int, float>
     */
    protected function applyMinimumWidths(array $percentages): array
    {
        $min = $this->getMinSegmentWidth();

        if (blank($min) || ($min <= 0) || ($percentages === [])) {
            return $percentages;
        }

        $visibleCount = count(array_filter($percentages, fn (float $percentage): bool => $percentage > 0));

        if ($visibleCount === 0) {
            return $percentages;
        }

        // A minimum that cannot physically fit is capped, keeping widths sane
        // even with dozens of tiny segments.
        $min = min((float) $min, 100 / $visibleCount);

        $deficit = 0.0; // Total width owed to segments below the minimum.
        $surplus = 0.0; // Total width available above the minimum.

        foreach ($percentages as $percentage) {
            if ($percentage <= 0) {
                continue;
            }

            if ($percentage < $min) {
                $deficit += $min - $percentage;
            } else {
                $surplus += $percentage - $min;
            }
        }

        if (($deficit <= 0) || ($surplus <= 0)) {
            return $percentages;
        }

        $shrinkFactor = max($surplus - $deficit, 0) / $surplus;

        return array_map(
            function (float $percentage) use ($min, $shrinkFactor): float {
                if ($percentage <= 0) {
                    return $percentage;
                }

                return ($percentage < $min)
                    ? $min
                    : $min + (($percentage - $min) * $shrinkFactor);
            },
            $percentages,
        );
    }

    /**
     * Resolves a segment's color into the pair consumed by the view:
     * utility classes for registered semantic colors, or inline CSS custom
     * properties for palettes and raw CSS colors.
     *
     * @param  string | array<int, string> | null  $color
     * @return array{classes: array<int, string>, styles: string | null}
     */
    protected function resolveSegmentColor(string | array | null $color, int $index): array
    {
        if (blank($color)) {
            $fallback = $this->evaluate($this->fallbackColors) ?: ['primary'];
            $color = $fallback[$index % count($fallback)];
        }

        // A full palette (e.g. `Color::Purple` or `Color::hex('#8b5cf6')`)
        // goes through Filament's contrast-aware custom style resolver.
        if (is_array($color)) {
            return [
                'classes' => ['fi-color', 'fi-color-custom'],
                'styles' => implode(';', FilamentColor::getComponentCustomStyles(MultiProgressSegmentComponent::class, $color)),
            ];
        }

        // A registered color name resolves to cached utility classes.
        if (FilamentColor::getColor($color) !== null) {
            return [
                'classes' => get_component_color_classes(MultiProgressSegmentComponent::class, $color),
                'styles' => null,
            ];
        }

        // Anything else is treated as a raw CSS color (hex, rgb(), oklch(),
        // var(--...), ...) and used verbatim in both themes.
        return [
            'classes' => ['fi-color', 'fi-color-custom'],
            'styles' => "--bg: {$color};--dark-bg: {$color}",
        ];
    }

    /**
     * Builds a segment's tooltip: the per-segment override wins, then the
     * {@see formatSegmentTooltipUsing()} callback, then a generated
     * "label / value / percentage" tooltip.
     *
     * @param  array<string, mixed>  $segment
     */
    protected function resolveSegmentTooltip(array $segment): string | Htmlable | null
    {
        if (filled($segment['tooltip'] ?? null)) {
            return $segment['tooltip'];
        }

        $formattedValue = $this->formatValue($segment['value'], $segment);
        $formattedPercentage = $this->formatPercentage($segment['percentage']);

        if ($this->formatSegmentTooltipUsing instanceof Closure) {
            return $this->evaluate($this->formatSegmentTooltipUsing, [
                'segment' => [
                    ...$segment,
                    'formattedValue' => $formattedValue,
                    'formattedPercentage' => $formattedPercentage,
                ],
            ]);
        }

        $lines = array_filter([
            filled($segment['label'] ?? null) ? ('<strong>' . e($segment['label']) . '</strong>') : null,
            e($formattedValue),
            e($formattedPercentage),
        ]);

        return new HtmlString(implode('<br>', $lines));
    }

    /**
     * @param  array<string, mixed> | null  $segment
     */
    protected function formatValue(int | float $value, ?array $segment = null): string
    {
        if ($this->formatValueUsing instanceof Closure) {
            return (string) $this->evaluate($this->formatValueUsing, [
                'state' => $value,
                'value' => $value,
                'segment' => $segment,
            ]);
        }

        $formatted = number_format($value, (floor($value) === (float) $value) ? 0 : 2);

        $suffix = $this->evaluate($this->valueSuffix);

        return filled($suffix) ? "{$formatted} {$suffix}" : $formatted;
    }

    protected function formatPercentage(float $percentage): string
    {
        if ($this->formatPercentageUsing instanceof Closure) {
            return (string) $this->evaluate($this->formatPercentageUsing, [
                'state' => $percentage,
                'percentage' => $percentage,
            ]);
        }

        // One decimal at most, and only when meaningful: 70%, 12.5%.
        return round($percentage, 1) . '%';
    }

    /**
     * A screen-reader description of the whole bar, e.g.
     * "Translated: 70%, Needs Review: 20%, Missing: 10%".
     *
     * @param  array<int, array<string, mixed>>  $segments
     */
    protected function generateAriaLabel(array $segments, float $overallPercentage): string
    {
        if ($segments === []) {
            return __('filament-advanced-components::multi-progress.empty');
        }

        $parts = array_map(
            fn (array $segment): string => filled($segment['label'])
                ? "{$segment['label']}: {$segment['formattedPercentage']}"
                : $segment['formattedPercentage'],
            $segments,
        );

        return implode(', ', $parts);
    }

    protected function resolveIconHtml(string | BackedEnum | null $icon): ?Htmlable
    {
        if (blank($icon)) {
            return null;
        }

        return generate_icon_html($icon);
    }

    /**
     * Makes `$total` injectable into every user-provided closure, alongside
     * Filament's defaults (`$record`, `$state`, `$livewire`, ...).
     *
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'total' => [$this->getTotal()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
