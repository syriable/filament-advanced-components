<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Rendering;

use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Illuminate\View\ComponentAttributeBag;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\Orientation;

/**
 * The fully evaluated, render-ready state of a
 * {@see Separator}.
 *
 * By the time this object exists, every lazy value — label, icon, alignment,
 * spacing — has been resolved and, where it touches HTML output, escaped.
 * The Blade view only assembles markup from it, so evaluation and rendering
 * stay independent (mirroring {@see OptionViewModel}).
 */
readonly class SeparatorViewModel
{
    public function __construct(
        public Orientation $orientation,
        public string $variantClass,
        public ?string $labelHtml,
        public ?string $iconHtml,
        public IconPosition $iconPosition,
        public Alignment | string | null $alignment,
        public ?string $width,
        public ?string $padding,
        public ?string $spaceBefore,
        public ?string $spaceAfter,
        public ?string $color,
        public ?string $thickness,
        /** @var list<string> */
        public array $labelTypographyClasses,
        /** @var array<string, string> */
        public array $zigzagCssVariables,
        public ComponentAttributeBag $extraAttributes,
    ) {}

    public function isVertical(): bool
    {
        return $this->orientation === Orientation::Vertical;
    }

    public function hasLabel(): bool
    {
        return $this->labelHtml !== null && $this->labelHtml !== '';
    }

    public function hasIcon(): bool
    {
        return $this->iconHtml !== null && $this->iconHtml !== '';
    }

    public function hasContent(): bool
    {
        return $this->hasLabel() || $this->hasIcon();
    }

    public function isIconBeforeLabel(): bool
    {
        return $this->iconPosition === IconPosition::Before;
    }

    public function isColored(): bool
    {
        return $this->color !== null && $this->color !== '';
    }

    public function alignmentClass(): ?string
    {
        $alignment = $this->alignment instanceof Alignment ? $this->alignment->value : $this->alignment;

        return filled($alignment) ? "fi-separator-align-{$alignment}" : null;
    }

    /**
     * Per-instance sizing, passed through as CSS custom properties so the
     * stylesheet itself stays static and cacheable.
     */
    public function rootStyles(): string
    {
        $styles = array_filter([
            filled($this->width) ? "--fi-separator-width: {$this->width}" : null,
            filled($this->padding) ? "--fi-separator-content-gap: {$this->padding}" : null,
            filled($this->thickness) ? "--fi-separator-thickness: {$this->thickness}" : null,
            $this->isColored() ? "--fi-separator-color: {$this->color}" : null,
            '--fi-separator-space-before: ' . ($this->spaceBefore ?? '0px'),
            '--fi-separator-space-after: ' . ($this->spaceAfter ?? '0px'),
        ]);

        foreach ($this->zigzagCssVariables as $property => $value) {
            $styles[] = "{$property}: {$value}";
        }

        return implode(';', $styles);
    }
}
