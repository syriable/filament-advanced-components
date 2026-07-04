<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support;

use Illuminate\Support\HtmlString;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

/**
 * Renders the affix `<img>` tags for
 * {@see AdvancedTextColumn}.
 *
 * Kept as a dedicated class so subclasses can swap it out (override
 * `AdvancedTextColumn::getImageRenderer()`) to customize the markup —
 * lazy-loading strategies, `srcset`, wrapping in a lightbox trigger, etc.
 */
class ImageRenderer
{
    /**
     * @param  array<string, string | null>  $attributes
     */
    public function render(string $url, string $size, ?string $borderRadius = null, string $alt = '', array $attributes = []): HtmlString
    {
        $style = "width: {$size}; height: {$size};";

        if ($borderRadius !== null) {
            $style .= " border-radius: {$borderRadius};";
        }

        $attributes = [
            'src' => $url,
            'alt' => $alt,
            'style' => $style,
            'loading' => 'lazy',
            'class' => 'fi-adv-text-image',
            ...$attributes,
        ];

        $html = '<img';

        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }

            $html .= ' ' . $name . '="' . e($value) . '"';
        }

        $html .= ' />';

        return new HtmlString($html);
    }
}
