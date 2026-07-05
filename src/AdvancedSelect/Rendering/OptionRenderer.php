<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Rendering;

use Filament\Support\Components\ViewComponent;
use Illuminate\View\ComponentAttributeBag;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support\ColorResolver;

/**
 * The default {@see RendersOptions} implementation.
 *
 * It assembles a small, dependency-free HTML fragment per option. The dropdown
 * row is laid out as an icon beside a body; the body's first line holds the
 * label and badge together, and the description — when present — sits on its
 * own line beneath them, so the badge always hugs the label and the
 * description can appear or disappear without shifting anything else:
 *
 * ```
 * [icon]  Label  [Badge]
 *         Description line
 * ```
 *
 * It never escapes the icon markup (already safe SVG from Filament) but always
 * escapes the label, description, and badge text, so developer-supplied strings
 * can contain markup characters without breaking out.
 *
 * Subclass and override a single `render*` method to tweak one surface, or
 * bind a wholly different implementation against the contract.
 */
class OptionRenderer implements RendersOptions
{
    public function renderOption(OptionViewModel $option, ViewComponent $component): string
    {
        $attributes = $this->rootAttributes($option, ['fi-adv-select-option']);

        $head = '<span class="fi-adv-select-option-head">'
            . '<span class="fi-adv-select-option-label">' . e($option->label) . '</span>'
            . $this->badgeHtml($option)
            . '</span>';

        $description = $option->hasDescription()
            ? '<span class="fi-adv-select-option-description">' . e((string) $option->description) . '</span>'
            : '';

        return '<div ' . $attributes->toHtml() . '>'
            . $this->iconHtml($option)
            . '<span class="fi-adv-select-option-body">' . $head . $description . '</span>'
            . '</div>';
    }

    public function renderSelectedLabel(OptionViewModel $option, ViewComponent $component): string
    {
        $attributes = $this->rootAttributes($option, ['fi-adv-select-option', 'fi-adv-select-option-selected']);

        return '<div ' . $attributes->toHtml() . '>'
            . $this->iconHtml($option)
            . '<span class="fi-adv-select-option-label">' . e($option->label) . '</span>'
            . $this->badgeHtml($option)
            . '</div>';
    }

    /**
     * @param  array<string>  $baseClasses
     */
    protected function rootAttributes(OptionViewModel $option, array $baseClasses): ComponentAttributeBag
    {
        $attributes = (new ComponentAttributeBag($option->extraAttributes))
            ->class([...$baseClasses, ...$option->extraClasses]);

        if (filled($option->color)) {
            $attributes = $attributes->style([
                '--fi-adv-select-option-color: ' . ColorResolver::toCss($option->color),
            ]);
        }

        return $attributes;
    }

    protected function iconHtml(OptionViewModel $option): string
    {
        if (! $option->hasIcon()) {
            return '';
        }

        return '<span class="fi-adv-select-option-icon">' . $option->iconHtml . '</span>';
    }

    protected function badgeHtml(OptionViewModel $option): string
    {
        if (! $option->hasBadge()) {
            return '';
        }

        $attributes = (new ComponentAttributeBag)->class([
            'fi-adv-select-option-badge',
            'fi-adv-select-option-badge-end' => $option->badgeAtEnd(),
        ]);

        if (filled($option->badgeColor)) {
            $attributes = $attributes->style([
                '--fi-adv-select-badge-color: ' . ColorResolver::toCss($option->badgeColor),
            ]);
        }

        return '<span ' . $attributes->toHtml() . '>' . e((string) $option->badgeLabel) . '</span>';
    }
}
