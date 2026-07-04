<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges;

use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\BadgeComponent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\RendersBadges;

use function Filament\Support\generate_href_html;

/**
 * Default {@see RendersBadges} implementation.
 *
 * Produces the same markup as a native Filament badge (`fi-badge` plus the
 * theme's color classes), one element per badge, assembled as plain strings
 * for table-scale performance. Clickable badges render as real anchors, or
 * receive button semantics (`role`, `tabindex`, Enter/Space activation) so
 * they stay keyboard-accessible.
 */
class BadgeRenderer implements RendersBadges
{
    public function render(BadgeViewModel $badge): string
    {
        $classes = ['fi-badge', 'fi-size-' . ($badge->size ?? 'sm')];
        $styles = $badge->styles;

        $color = $badge->color ?? 'primary';

        if (is_array($color)) {
            $classes[] = 'fi-color';

            foreach (FilamentColor::getComponentCustomStyles(BadgeComponent::class, $color) as $style) {
                [$property, $value] = explode(':', $style, 2);
                $styles[trim($property)] ??= trim($value);
            }
        } else {
            array_push($classes, ...FilamentColor::getComponentClasses(BadgeComponent::class, $color));
        }

        array_push($classes, ...$badge->classes);

        $isAnchor = $badge->url !== null;
        $tag = $isAnchor ? 'a' : 'span';

        $html = '<' . $tag;

        if ($isAnchor) {
            $html .= ' ' . generate_href_html($badge->url, $badge->shouldOpenUrlInNewTab)->toHtml();
        } elseif ($badge->isClickable) {
            // Not a link, but interactive: give it button semantics and
            // keyboard activation.
            $html .= ' role="button" tabindex="0"';
            $html .= ' x-on:keydown.enter.prevent="$el.click()" x-on:keydown.space.prevent="$el.click()"';
        }

        $html .= ' class="' . e(implode(' ', array_unique($classes))) . '"';

        if ($styles !== []) {
            $styleString = implode('; ', array_map(
                fn (string $property, string $value): string => "{$property}: {$value}",
                array_keys($styles),
                $styles,
            ));

            $html .= ' style="' . e($styleString) . '"';
        }

        if (filled($badge->tooltip)) {
            // Mirrors Filament's own badge generator: Js::from() output is
            // attribute-safe as-is (it unicode-escapes quotes and angle
            // brackets), so it must not be entity-escaped again.
            $html .= ' x-tooltip="{
                content: ' . Js::from($badge->tooltip)->toHtml() . ',
                theme: $store.theme,
                allowHTML: ' . Js::from($badge->tooltip instanceof Htmlable)->toHtml() . ',
            }"';
        }

        foreach ($badge->extraAttributes as $attribute => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === false) {
                continue;
            }
            $html .= ' ' . $attribute . '="' . e((string) $value) . '"';
        }

        $html .= '>';

        if (! $badge->isIconAfterLabel) {
            $html .= $badge->iconHtml;
        }

        $html .= '<span class="fi-badge-label-ctn"><span class="fi-badge-label">' . e($badge->label) . '</span></span>';

        if ($badge->isIconAfterLabel) {
            $html .= $badge->iconHtml;
        }

        return $html . '</' . $tag . '>';
    }

    public function renderCollection(iterable $badges): string
    {
        $html = '';

        foreach ($badges as $badge) {
            $html .= $this->render($badge);
        }

        if ($html === '') {
            return '';
        }

        return '<span class="fi-adv-badges">' . $html . '</span>';
    }
}
