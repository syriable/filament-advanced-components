<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges;

use BackedEnum;
use Closure;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Concerns\Macroable;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Columns\Concerns\CanBeHiddenResponsively;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\AdvancedTextEntry;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

use function Filament\Support\generate_icon_html;

/**
 * A fluent, lazily evaluated badge attached to an
 * {@see AdvancedTextColumn}
 * or {@see AdvancedTextEntry}
 * via `badges()`.
 *
 * ```php
 * AdvancedTextColumn::make('status')
 *     ->badges([
 *         AdvancedBadge::make('Verified')->color('success')->border()->pulse(),
 *         AdvancedBadge::make(fn (User $record): string => $record->plan)
 *             ->color(fn (User $record): string => $record->onTrial() ? 'warning' : 'info')
 *             ->visible(fn (User $record): bool => $record->plan !== null),
 *     ]);
 * ```
 *
 * A badge instance is shared across every row of the table: it stores raw
 * configuration only, and is resolved per cell into an immutable
 * {@see BadgeViewModel} using the owning component's evaluation context —
 * so every option accepts a static value or a closure with the usual
 * `$record`, `$state`, `$livewire`, and `$table` injections.
 *
 * Extensibility: subclass it, add macros (`AdvancedBadge::macro()`), or
 * register reusable presets (`AdvancedBadge::registerPreset()`).
 */
class AdvancedBadge
{
    use Conditionable;
    use Macroable;

    /**
     * @var array<string, Closure(static): void>
     */
    protected static array $presets = [];

    protected bool | Closure $shouldTranslateLabel = false;

    protected string | BackedEnum | Closure | null $icon = null;

    protected IconPosition | string | Closure $iconPosition = IconPosition::Before;

    protected string | Closure | null $iconColor = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $color = null;

    protected string | Closure | null $backgroundColor = null;

    protected string | Closure | null $textColor = null;

    protected bool | Closure $hasBorder = false;

    protected string | Closure | null $borderColor = null;

    protected int | string | Closure | null $borderWidth = null;

    protected int | string | Closure | null $borderRadius = null;

    protected bool | Closure $isPill = false;

    protected bool | Closure $isOutlined = false;

    protected bool | Closure | null $isFilled = null;

    protected Size | string | Closure | null $size = null;

    /**
     * @var array<int | string, string | Closure>
     */
    protected array $animations = [];

    /**
     * @var array<string> | string | Closure
     */
    protected array | string | Closure $extraClasses = [];

    /**
     * @var array<string, mixed> | Closure
     */
    protected array | Closure $extraAttributes = [];

    protected string | Htmlable | Closure | null $tooltip = null;

    protected bool | Closure $isVisible = true;

    protected bool | Closure $isHidden = false;

    protected string | Closure | null $hiddenFrom = null;

    protected string | Closure | null $visibleFrom = null;

    protected string | Closure | null $authorization = null;

    protected mixed $authorizationArguments = null;

    protected string | Closure | null $url = null;

    protected bool | Closure $shouldOpenUrlInNewTab = false;

    protected string | Closure | null $wireClickAction = null;

    protected string | Closure | null $alpineClickHandler = null;

    final public function __construct(protected string | Htmlable | Closure | null $label = null) {}

    public static function make(string | Htmlable | Closure | null $label = null): static
    {
        return new static($label);
    }

    /**
     * Register a reusable preset that any badge can apply with `preset()`.
     *
     * ```php
     * AdvancedBadge::registerPreset('pii', fn (AdvancedBadge $badge) => $badge
     *     ->color('danger')
     *     ->outline()
     *     ->tooltip('Contains personal data'));
     * ```
     *
     * @param  Closure(static): void  $configure
     */
    public static function registerPreset(string $name, Closure $configure): void
    {
        static::$presets[$name] = $configure;
    }

    /**
     * Apply a preset registered with `registerPreset()`.
     */
    public function preset(string $name): static
    {
        $preset = static::$presets[$name] ?? throw new InvalidArgumentException(
            "Badge preset [{$name}] is not registered. Register it with " . static::class . '::registerPreset().',
        );

        $preset($this);

        return $this;
    }

    public function label(string | Htmlable | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Pass the (string) label through the translator before rendering.
     */
    public function translateLabel(bool | Closure $shouldTranslate = true): static
    {
        $this->shouldTranslateLabel = $shouldTranslate;

        return $this;
    }

    public function icon(string | BackedEnum | Closure | null $icon, IconPosition | string | Closure | null $position = null): static
    {
        $this->icon = $icon;

        if ($position !== null) {
            $this->iconPosition = $position;
        }

        return $this;
    }

    public function iconPosition(IconPosition | string | Closure $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    /**
     * Color of the icon only. Accepts a semantic Filament color name
     * (`success`, `danger`, …) or any CSS color.
     */
    public function iconColor(string | Closure | null $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    /**
     * The badge's Filament color — a semantic name (`primary`, `success`,
     * …) or a `Color` palette array, resolved exactly like a native badge.
     *
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function color(string | array | Closure | null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Override the background only. Accepts a semantic Filament color name
     * or any CSS color, applied in both light and dark mode.
     */
    public function backgroundColor(string | Closure | null $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    /**
     * Override the text color only. Accepts a semantic Filament color name
     * or any CSS color.
     */
    public function textColor(string | Closure | null $color): static
    {
        $this->textColor = $color;

        return $this;
    }

    public function border(bool | Closure $condition = true): static
    {
        $this->hasBorder = $condition;

        return $this;
    }

    public function borderColor(string | Closure | null $color): static
    {
        $this->hasBorder = true;
        $this->borderColor = $color;

        return $this;
    }

    /**
     * Border width; integers are pixels.
     */
    public function borderWidth(int | string | Closure | null $width): static
    {
        $this->hasBorder = true;
        $this->borderWidth = $width;

        return $this;
    }

    /**
     * Border radius; integers are pixels.
     */
    public function borderRadius(int | string | Closure | null $radius): static
    {
        $this->borderRadius = $radius;

        return $this;
    }

    /**
     * Alias of `borderRadius()` with a subtle default.
     */
    public function rounded(int | string | Closure $radius = '0.375rem'): static
    {
        return $this->borderRadius($radius);
    }

    /**
     * Fully rounded, capsule-shaped badge.
     */
    public function pill(bool | Closure $condition = true): static
    {
        $this->isPill = $condition;

        return $this;
    }

    /**
     * Transparent background with a `currentColor` border.
     */
    public function outline(bool | Closure $condition = true): static
    {
        $this->isOutlined = $condition;

        return $this;
    }

    /**
     * The default solid style; the explicit inverse of `outline()`.
     */
    public function filled(bool | Closure $condition = true): static
    {
        $this->isFilled = $condition;

        return $this;
    }

    public function size(Size | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Apply a registered animation (see {@see BadgeAnimations}); the
     * condition makes it record-aware.
     */
    public function animation(string $name, bool | Closure $condition = true): static
    {
        $this->animations[$name] = $condition;

        return $this;
    }

    public function pulse(bool | Closure $condition = true): static
    {
        return $this->animation('pulse', $condition);
    }

    public function bounce(bool | Closure $condition = true): static
    {
        return $this->animation('bounce', $condition);
    }

    /**
     * Additional CSS classes on the badge element.
     *
     * @param  array<string> | string | Closure  $classes
     */
    public function classes(array | string | Closure $classes): static
    {
        $this->extraClasses = $classes;

        return $this;
    }

    /**
     * Additional HTML attributes on the badge element — the escape hatch
     * for any Alpine.js (`x-on:click`, `x-data`, …) or Livewire
     * (`wire:…`) interaction:
     *
     * ```php
     * ->extraAttributes(['x-on:mouseenter' => 'hovered = true'])
     * ```
     *
     * @param  array<string, mixed> | Closure  $attributes
     */
    public function extraAttributes(array | Closure $attributes): static
    {
        $this->extraAttributes = $attributes;

        return $this;
    }

    public function tooltip(string | Htmlable | Closure | null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function hidden(bool | Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * Hide the badge from the given breakpoint upward — the same responsive
     * API as Filament's columns
     * ({@see CanBeHiddenResponsively}).
     * Accepts `sm`, `md`, `lg`, `xl`, or `2xl`.
     *
     * ```php
     * AdvancedBadge::make('Beta')->hiddenFrom('lg');
     * ```
     */
    public function hiddenFrom(string | Closure | null $breakpoint): static
    {
        $this->hiddenFrom = $breakpoint;

        return $this;
    }

    /**
     * Only show the badge from the given breakpoint upward — hidden on
     * smaller screens. Accepts `sm`, `md`, `lg`, `xl`, or `2xl`.
     *
     * ```php
     * AdvancedBadge::make('Verified')->visibleFrom('md');
     * ```
     */
    public function visibleFrom(string | Closure | null $breakpoint): static
    {
        $this->visibleFrom = $breakpoint;

        return $this;
    }

    /**
     * Only render the badge when the current user passes the given gate
     * ability (checked against the record by default) or closure:
     *
     * ```php
     * ->authorize('viewSensitiveData')
     * ->authorize(fn (User $record): bool => auth()->user()->owns($record))
     * ```
     */
    public function authorize(string | Closure $abilityOrCondition, mixed $arguments = null): static
    {
        $this->authorization = $abilityOrCondition;
        $this->authorizationArguments = $arguments;

        return $this;
    }

    /**
     * Turn the badge into a link.
     */
    public function url(string | Closure | null $url, bool | Closure $shouldOpenInNewTab = false): static
    {
        $this->url = $url;
        $this->shouldOpenUrlInNewTab = $shouldOpenInNewTab;

        return $this;
    }

    public function openUrlInNewTab(bool | Closure $condition = true): static
    {
        $this->shouldOpenUrlInNewTab = $condition;

        return $this;
    }

    /**
     * Call a Livewire method when the badge is clicked (`wire:click`). The
     * badge becomes keyboard-focusable with button semantics.
     */
    public function wireClick(string | Closure | null $action): static
    {
        $this->wireClickAction = $action;

        return $this;
    }

    /**
     * Run an Alpine.js expression when the badge is clicked (`x-on:click`).
     * The badge becomes keyboard-focusable with button semantics.
     */
    public function alpineClick(string | Closure | null $handler): static
    {
        $this->alpineClickHandler = $handler;

        return $this;
    }

    /**
     * Resolve the badge against the owning component's evaluation context
     * into a render-ready view model — or `null` when the badge is hidden,
     * unauthorized, or has an empty label.
     *
     * `$isNestedInInteractiveElement` signals that the badge ends up inside
     * a cell-level `<a>` or `<button>`, where nesting another interactive
     * element is invalid HTML: click handlers then get `prevent`/`stop`
     * modifiers, and the renderer swaps anchors for scripted `role="link"`
     * elements.
     */
    public function resolve(ViewComponent $component, bool $isNestedInInteractiveElement = false): ?BadgeViewModel
    {
        $evaluate = fn (mixed $value): mixed => $component->evaluate($value, ['badge' => $this]);

        if ($evaluate($this->isHidden) || ! $evaluate($this->isVisible)) {
            return null;
        }

        if (! $this->isAuthorized($component)) {
            return null;
        }

        $label = $evaluate($this->label);

        if ($label instanceof Htmlable) {
            $label = trim(strip_tags($label->toHtml()));
        }

        if (blank($label)) {
            return null;
        }

        $label = (string) $label;

        if ($evaluate($this->shouldTranslateLabel)) {
            $label = __($label);
        }

        $styles = $this->resolveStyles($evaluate);
        $classes = $this->resolveClasses($evaluate);

        $url = $evaluate($this->url);
        $wireClick = $evaluate($this->wireClickAction);
        $alpineClick = $evaluate($this->alpineClickHandler);

        $extraAttributes = $evaluate($this->extraAttributes) ?? [];

        if (filled($wireClick)) {
            // Inside a linked cell, the click must neither bubble to the
            // wrapper nor trigger its navigation.
            $extraAttributes[$isNestedInInteractiveElement ? 'wire:click.prevent.stop' : 'wire:click'] = $wireClick;
        }

        if (filled($alpineClick)) {
            $extraAttributes[$isNestedInInteractiveElement ? 'x-on:click.stop.prevent' : 'x-on:click'] = $alpineClick;
        }

        return new BadgeViewModel(
            label: $label,
            color: $evaluate($this->color),
            size: $this->resolveSize($evaluate),
            iconHtml: $this->resolveIconHtml($evaluate),
            isIconAfterLabel: $this->resolveIconPosition($evaluate) === IconPosition::After,
            classes: $classes,
            styles: $styles,
            extraAttributes: $extraAttributes,
            tooltip: $evaluate($this->tooltip),
            url: filled($url) ? (string) $url : null,
            shouldOpenUrlInNewTab: (bool) $evaluate($this->shouldOpenUrlInNewTab),
            isClickable: filled($url) || filled($wireClick) || filled($alpineClick),
            isNestedInInteractiveElement: $isNestedInInteractiveElement,
        );
    }

    protected function isAuthorized(ViewComponent $component): bool
    {
        if ($this->authorization === null) {
            return true;
        }

        if ($this->authorization instanceof Closure) {
            return (bool) $component->evaluate($this->authorization, ['badge' => $this]);
        }

        $arguments = $component->evaluate($this->authorizationArguments, ['badge' => $this])
            ?? $component->evaluate(fn (mixed $record = null): mixed => $record);

        return Gate::allows($this->authorization, $arguments);
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     * @return array<string, string>
     */
    protected function resolveStyles(Closure $evaluate): array
    {
        $styles = [];

        if (filled($backgroundColor = $evaluate($this->backgroundColor))) {
            $styles['background-color'] = static::resolveCssColor($backgroundColor);
        }

        if (filled($textColor = $evaluate($this->textColor))) {
            $styles['color'] = static::resolveCssColor($textColor);
        }

        if ($evaluate($this->hasBorder)) {
            $borderWidth = $evaluate($this->borderWidth) ?? 1;
            $styles['border-width'] = is_int($borderWidth) ? "{$borderWidth}px" : (string) $borderWidth;
            $styles['border-style'] = 'solid';

            if (filled($borderColor = $evaluate($this->borderColor))) {
                $styles['border-color'] = static::resolveCssColor($borderColor);
            }
        }

        if (filled($borderRadius = $evaluate($this->borderRadius))) {
            $styles['border-radius'] = is_int($borderRadius) ? "{$borderRadius}px" : (string) $borderRadius;
        }

        return $styles;
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     * @return array<string>
     */
    protected function resolveClasses(Closure $evaluate): array
    {
        $classes = [];

        $isOutlined = ($this->isFilled !== null)
            ? ! $evaluate($this->isFilled)
            : (bool) $evaluate($this->isOutlined);

        if ($isOutlined) {
            $classes[] = 'fi-adv-badge-outline';
        }

        if ($evaluate($this->isPill)) {
            $classes[] = 'fi-adv-badge-pill';
        }

        foreach ($this->animations as $animation => $condition) {
            if ($evaluate($condition)) {
                $classes[] = BadgeAnimations::resolve((string) $animation);
            }
        }

        if (filled($hiddenFrom = $evaluate($this->hiddenFrom))) {
            $classes[] = 'fi-adv-badge-hidden-from-' . $hiddenFrom;
        }

        if (filled($visibleFrom = $evaluate($this->visibleFrom))) {
            $classes[] = 'fi-adv-badge-visible-from-' . $visibleFrom;
        }

        $extraClasses = $evaluate($this->extraClasses);

        foreach ((array) $extraClasses as $extraClass) {
            if (filled($extraClass)) {
                $classes[] = (string) $extraClass;
            }
        }

        return $classes;
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     */
    protected function resolveSize(Closure $evaluate): ?string
    {
        $size = $evaluate($this->size);

        if ($size instanceof Size) {
            return $size->value;
        }

        return filled($size) ? (string) $size : null;
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     */
    protected function resolveIconPosition(Closure $evaluate): IconPosition
    {
        $position = $evaluate($this->iconPosition);

        if (is_string($position)) {
            return IconPosition::tryFrom($position) ?? IconPosition::Before;
        }

        return $position;
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     */
    protected function resolveIconHtml(Closure $evaluate): string
    {
        $icon = $evaluate($this->icon);

        if (blank($icon)) {
            return '';
        }

        $attributes = new ComponentAttributeBag;

        if (filled($iconColor = $evaluate($this->iconColor))) {
            $attributes = $attributes->style([
                'color: ' . static::resolveCssColor($iconColor),
            ]);
        }

        return generate_icon_html($icon, attributes: $attributes, size: IconSize::Small)?->toHtml() ?? '';
    }

    /**
     * Map a registered Filament color name (`success`, `danger`, custom
     * palette names, …) onto its theme CSS variable, and pass anything else
     * through as a literal CSS color.
     */
    public static function resolveCssColor(string $color): string
    {
        if (array_key_exists($color, FilamentColor::getColors())) {
            return "var(--color-{$color}-600)";
        }

        return $color;
    }
}
