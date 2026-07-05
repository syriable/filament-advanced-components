<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\CanBeHidden;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Schemas\Components\Concerns\HasMaxWidth;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Concerns\Configurable;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Concerns\HasAlignment;
use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Concerns\HasIcon;
use Filament\Support\Concerns\HasIconPosition;
use Filament\Support\Concerns\Macroable;
use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support\ColorResolver;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns\HasOrientation;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns\HasSpacing;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns\HasVariant;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns\HasWidth;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Rendering\SeparatorRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Rendering\SeparatorViewModel;

/**
 * A purely visual layout component that divides a schema into groups — the
 * schema counterpart of an HTML `<hr>`, with an optional centered label,
 * icon, and configurable line style:
 *
 * ```php
 * Separator::make();
 *
 * Separator::make('General Information');
 *
 * Separator::make()
 *     ->label('Billing')
 *     ->icon(Heroicon::CreditCard);
 *
 * Separator::make()
 *     ->text('OR')
 *     ->dashed()
 *     ->alignCenter();
 *
 * Separator::make('Danger Zone')
 *     ->color('danger'); // a registered name, Color::Blue, or a raw '#22d3ee'
 * ```
 *
 * ## What it is not
 *
 * `Separator` never reads or writes state. It has no field name, does not
 * access a record's attributes, does not participate in validation, and
 * contributes nothing to a form's dehydrated payload. It exists purely for
 * visual organization, alongside Filament's own layout components —
 * `Section`, `Grid`, `Fieldset` — which is exactly what it extends: plain
 * {@see Component}, not `Entry` or `Field`.
 *
 * ## What it inherits for free
 *
 * Because it extends the base schema `Component` rather than reinventing
 * its plumbing, a `Separator` already has, with no extra code in this
 * class:
 *
 *  - `hidden()` / `visible()` (and JS variants) — {@see CanBeHidden}
 *  - `extraAttributes()` — {@see HasExtraAttributes}
 *  - `maxWidth()` — {@see HasMaxWidth}, applied to the
 *    outer grid cell by the schema renderer itself
 *  - `columnSpan()`, `columnStart()` and the rest of the grid placement API
 *  - lazy Closure evaluation for every configurable value — {@see EvaluatesClosures}
 *  - `macro()` / `mixin()` for extending the class without subclassing, and
 *    `configureUsing()` for registering reusable presets — {@see Macroable},
 *    {@see Configurable}
 *
 * ## Architecture
 *
 * Responsibilities are split across small, single-purpose collaborators,
 * each covering one concern:
 *
 *  - {@see HasOrientation} — horizontal vs. vertical;
 *  - {@see HasVariant} — line pattern and tone, extensible with custom names;
 *  - {@see HasSpacing}, {@see HasWidth} — sizing;
 *  - `HasLabel`, `HasIcon`, `HasIconPosition`, `HasAlignment`, `HasColor` —
 *    Filament's own concerns, reused as-is; the resolved color is turned into
 *    a CSS value by {@see ColorResolver};
 *  - {@see SeparatorViewModel} —
 *    the resolved, render-ready state;
 *  - {@see RendersSeparator} — the swappable renderer, bound to
 *    {@see SeparatorRenderer}
 *    by default; rebind it in your own service provider to change how every
 *    separator in the app renders.
 */
class Separator extends Component implements HasEmbeddedView
{
    use HasAlignment;
    use HasColor;
    use HasIcon;
    use HasIconPosition;
    use HasLabel;
    use HasOrientation;
    use HasSpacing;
    use HasVariant;
    use HasWidth;

    final public function __construct(string | Htmlable | Closure | null $label = null)
    {
        $this->label($label);
    }

    public static function make(string | Htmlable | Closure | null $label = null): static
    {
        $static = app(static::class, ['label' => $label]);
        $static->configure();

        return $static;
    }

    /**
     * A decorative alias for {@see label()}, reading naturally for a plain
     * divider: `Separator::make()->text('OR')`.
     */
    public function text(string | Htmlable | Closure | null $text): static
    {
        return $this->label($text);
    }

    public function toEmbeddedHtml(): string
    {
        return app(RendersSeparator::class)->render($this);
    }
}
