<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges;

use Illuminate\Contracts\Support\Htmlable;

/**
 * The fully evaluated, render-ready state of one badge for one cell.
 *
 * All lazy configuration has been resolved by the time this object exists —
 * the renderer only assembles markup. Keeping evaluation and rendering apart
 * lets either side be swapped independently (see the `RendersBadges`
 * contract), and keeps the per-row allocation footprint to a single small
 * value object.
 */
readonly class BadgeViewModel
{
    /**
     * @param  string | array<int | string, string | int> | null  $color
     * @param  array<string>  $classes
     * @param  array<string, string>  $styles
     * @param  array<string, mixed>  $extraAttributes
     */
    public function __construct(
        public string $label,
        public string | array | null $color = null,
        public ?string $size = null,
        public string $iconHtml = '',
        public bool $isIconAfterLabel = false,
        public array $classes = [],
        public array $styles = [],
        public array $extraAttributes = [],
        public string | Htmlable | null $tooltip = null,
        public ?string $url = null,
        public bool $shouldOpenUrlInNewTab = false,
        public bool $isClickable = false,
    ) {}
}
