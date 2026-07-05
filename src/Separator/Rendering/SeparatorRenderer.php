<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Rendering;

use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support\ColorResolver;
use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;

use function Filament\Support\generate_icon_html;

/**
 * The default {@see RendersSeparator} implementation: builds a
 * {@see SeparatorViewModel} from the component's evaluated configuration,
 * then hands it to the package's Blade view.
 *
 * All escaping and icon generation happens once per render, here — never in
 * the view — so the Blade template stays a dumb template even when a
 * separator sits inside a large, repeated schema.
 */
class SeparatorRenderer implements RendersSeparator
{
    public function render(Separator $separator): string
    {
        return view('filament-advanced-components::components.separator', [
            'separator' => $this->buildViewModel($separator),
        ])->render();
    }

    protected function buildViewModel(Separator $separator): SeparatorViewModel
    {
        $icon = $separator->getIcon();

        return new SeparatorViewModel(
            orientation: $separator->getOrientation(),
            variantClass: $separator->getVariantClass(),
            labelHtml: $this->resolveLabelHtml($separator),
            iconHtml: filled($icon) ? generate_icon_html($icon)?->toHtml() : null,
            iconPosition: $separator->getIconPosition(),
            alignment: $separator->getAlignment(),
            width: $separator->getWidth(),
            padding: $separator->getPadding(),
            spaceBefore: $separator->getSpaceBefore(),
            spaceAfter: $separator->getSpaceAfter(),
            color: $this->resolveColor($separator),
            extraAttributes: $separator->getExtraAttributeBag(),
        );
    }

    /**
     * Resolves the configured color — a registered Filament name (`info`,
     * `gray`, …), a raw `Color` palette array (`Color::Blue`), or a literal
     * CSS color (`#22d3ee`) — into a single CSS value the stylesheet can
     * tint the line, label, and icon with. Null when no color is set, so
     * the separator keeps its neutral gray defaults.
     */
    protected function resolveColor(Separator $separator): ?string
    {
        $color = $separator->getColor();

        return filled($color) ? ColorResolver::toCss($color) : null;
    }

    protected function resolveLabelHtml(Separator $separator): ?string
    {
        if ($separator->isLabelHidden()) {
            return null;
        }

        $label = $separator->getLabel();

        if (blank($label)) {
            return null;
        }

        return $label instanceof Htmlable ? $label->toHtml() : e($label);
    }
}
