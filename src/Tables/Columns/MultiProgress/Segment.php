<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgress;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgressColumn;

/**
 * A fluent, type-safe value object describing a single slice of a
 * {@see MultiProgressColumn}.
 *
 * Using this object is optional — plain arrays with the same keys are
 * accepted everywhere a segment is expected:
 *
 * ```php
 * MultiProgressColumn::make('translation_progress')
 *     ->segments(fn ($record) => [
 *         Segment::make('Translated')->value(420)->color('success'),
 *         ['label' => 'Missing', 'value' => 60, 'color' => 'danger'],
 *     ]);
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class Segment implements Arrayable
{
    protected int | float $value = 0;

    /**
     * A Filament semantic color name (`success`, `warning`, `danger`, `info`,
     * `primary`, `gray`, or any custom color registered with `FilamentColor`),
     * a raw CSS color (`#8b5cf6`, `rgb(...)`, `oklch(...)`), or a full
     * Filament palette array (e.g. `Color::Purple`).
     *
     * @var string | array<int, string> | null
     */
    protected string | array | null $color = null;

    protected string | Htmlable | null $tooltip = null;

    protected string | BackedEnum | null $icon = null;

    protected ?string $badge = null;

    protected ?string $url = null;

    protected bool $shouldOpenUrlInNewTab = false;

    final public function __construct(
        protected string $label = '',
    ) {}

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The segment's magnitude. It may be a raw amount (e.g. `420` keys) or a
     * percentage — the column normalizes all values against the total.
     */
    public function value(int | float $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param  string | array<int, string> | null  $color
     */
    public function color(string | array | null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Overrides the auto-generated tooltip for this segment.
     */
    public function tooltip(string | Htmlable | null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    /**
     * An icon shown inside the tooltip and the legend entry.
     */
    public function icon(string | BackedEnum | null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * A small badge rendered next to the legend entry (e.g. a raw count).
     */
    public function badge(?string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Makes the segment a link.
     */
    public function url(?string $url, bool $shouldOpenInNewTab = false): static
    {
        $this->url = $url;
        $this->shouldOpenUrlInNewTab = $shouldOpenInNewTab;

        return $this;
    }

    public function openUrlInNewTab(bool $condition = true): static
    {
        $this->shouldOpenUrlInNewTab = $condition;

        return $this;
    }

    /**
     * @return array{
     *     label: string,
     *     value: int | float,
     *     color: string | array<int, string> | null,
     *     tooltip: string | Htmlable | null,
     *     icon: string | BackedEnum | null,
     *     badge: string | null,
     *     url: string | null,
     *     shouldOpenUrlInNewTab: bool,
     * }
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'color' => $this->color,
            'tooltip' => $this->tooltip,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'url' => $this->url,
            'shouldOpenUrlInNewTab' => $this->shouldOpenUrlInNewTab,
        ];
    }
}
