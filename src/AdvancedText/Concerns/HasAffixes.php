<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use BackedEnum;
use Closure;

/**
 * Images and icons rendered before or after the cell's content.
 *
 * ```php
 * AdvancedTextColumn::make('name')
 *     ->prefixImage(fn (User $record): string => $record->avatar_url)
 *     ->imageCircular()
 *     ->imageSize('2rem')
 *     ->suffixIcon(Heroicon::CheckBadge, color: 'success');
 * ```
 *
 * Unlike Filament's native `icon()` — which renders a single icon on one
 * side — a prefix and a suffix can be configured at the same time, and
 * images are supported in addition to icons.
 */
trait HasAffixes
{
    protected string | Closure | null $prefixImageUrl = null;

    protected string | Closure | null $suffixImageUrl = null;

    protected string | Closure | null $imageAlt = null;

    protected int | string | Closure $imageSize = '1.5rem';

    protected string | Closure | null $imageBorderRadius = null;

    protected string | Closure | null $imageFit = null;

    protected bool | Closure $isImageCircular = false;

    protected string | BackedEnum | Closure | null $prefixIcon = null;

    protected string | BackedEnum | Closure | null $suffixIcon = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $prefixIconColor = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $suffixIconColor = null;

    /**
     * Render an image before the content.
     */
    public function prefixImage(string | Closure | null $url): static
    {
        $this->prefixImageUrl = $url;

        return $this;
    }

    /**
     * Render an image after the content.
     */
    public function suffixImage(string | Closure | null $url): static
    {
        $this->suffixImageUrl = $url;

        return $this;
    }

    /**
     * Alt text for the affix images. Defaults to an empty string, marking
     * the images as decorative for screen readers.
     */
    public function imageAlt(string | Closure | null $alt): static
    {
        $this->imageAlt = $alt;

        return $this;
    }

    /**
     * Width and height of the affix images. Integers are pixels; strings are
     * used as-is, so any CSS length works.
     */
    public function imageSize(int | string | Closure $size): static
    {
        $this->imageSize = $size;

        return $this;
    }

    /**
     * Round the corners of the affix images. Integers are pixels; strings
     * are used as-is. Calling it without arguments applies a subtle default.
     */
    public function imageRounded(int | string | Closure $radius = '0.375rem'): static
    {
        $this->imageBorderRadius = is_int($radius) ? "{$radius}px" : $radius;

        return $this;
    }

    /**
     * How the image fills its box — any CSS `object-fit` value (`contain`,
     * `cover`, `fill`, `none`, `scale-down`). Defaults to `contain`.
     */
    public function imageFit(string | Closure | null $fit): static
    {
        $this->imageFit = $fit;

        return $this;
    }

    /**
     * Render the affix images as circular avatars.
     */
    public function imageCircular(bool | Closure $condition = true): static
    {
        $this->isImageCircular = $condition;

        return $this;
    }

    /**
     * Render an icon before the content. Both a prefix and a suffix icon may
     * be configured at once — something the native `icon()` cannot do.
     *
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function prefixIcon(string | BackedEnum | Closure | null $icon, string | array | Closure | null $color = null): static
    {
        $this->prefixIcon = $icon;

        if ($color !== null) {
            $this->prefixIconColor = $color;
        }

        return $this;
    }

    /**
     * Render an icon after the content.
     *
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function suffixIcon(string | BackedEnum | Closure | null $icon, string | array | Closure | null $color = null): static
    {
        $this->suffixIcon = $icon;

        if ($color !== null) {
            $this->suffixIconColor = $color;
        }

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function prefixIconColor(string | array | Closure | null $color): static
    {
        $this->prefixIconColor = $color;

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function suffixIconColor(string | array | Closure | null $color): static
    {
        $this->suffixIconColor = $color;

        return $this;
    }

    public function getPrefixImageUrl(): ?string
    {
        $url = $this->evaluate($this->prefixImageUrl);

        return filled($url) ? (string) $url : null;
    }

    public function getSuffixImageUrl(): ?string
    {
        $url = $this->evaluate($this->suffixImageUrl);

        return filled($url) ? (string) $url : null;
    }

    public function getImageAlt(): string
    {
        return (string) ($this->evaluate($this->imageAlt) ?? '');
    }

    public function getImageSize(): string
    {
        $size = $this->evaluate($this->imageSize);

        return is_int($size) ? "{$size}px" : (string) $size;
    }

    public function getImageFit(): ?string
    {
        $fit = $this->evaluate($this->imageFit);

        return filled($fit) ? (string) $fit : null;
    }

    public function getImageBorderRadius(): ?string
    {
        if ($this->evaluate($this->isImageCircular)) {
            return '50%';
        }

        $radius = $this->evaluate($this->imageBorderRadius);

        return filled($radius) ? (string) $radius : null;
    }

    public function getPrefixIcon(): string | BackedEnum | null
    {
        return $this->evaluate($this->prefixIcon);
    }

    public function getSuffixIcon(): string | BackedEnum | null
    {
        return $this->evaluate($this->suffixIcon);
    }

    /**
     * @return string | array<int | string, string | int> | null
     */
    public function getPrefixIconColor(): string | array | null
    {
        return $this->evaluate($this->prefixIconColor);
    }

    /**
     * @return string | array<int | string, string | int> | null
     */
    public function getSuffixIconColor(): string | array | null
    {
        return $this->evaluate($this->suffixIconColor);
    }

    protected function hasAffixes(): bool
    {
        if ($this->getPrefixImageUrl() !== null) {
            return true;
        }
        if ($this->getSuffixImageUrl() !== null) {
            return true;
        }
        if ($this->getPrefixIcon() !== null) {
            return true;
        }

        return $this->getSuffixIcon() !== null;
    }
}
