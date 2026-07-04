<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options;

use BackedEnum;
use Closure;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Concerns\Macroable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support\ColorResolver;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;
use UnitEnum;

use function Filament\Support\generate_icon_html;

/**
 * A fluent, lazily evaluated option attached to an {@see AdvancedSelect}
 * via `options()` / `option()`.
 *
 * ```php
 * AdvancedSelect::make('status')->options([
 *     SelectOption::make('draft', 'Draft')
 *         ->icon('heroicon-o-pencil-square')
 *         ->description('Only visible to you'),
 *     SelectOption::make('published', 'Published')
 *         ->icon('heroicon-o-globe-alt')
 *         ->color('success')
 *         ->badge('Live')
 *         ->disabled(fn (Post $record): bool => ! $record->is_ready),
 * ]);
 * ```
 *
 * A single option instance is shared across every render: it stores raw
 * configuration only and is resolved into an immutable {@see OptionViewModel}
 * using the owning component's evaluation context — so every setter accepts a
 * static value or a closure with the field's usual `$state`, `$get`, `$record`,
 * `$livewire`, and `$component` injections, plus `$option` (this instance).
 *
 * Extensibility: subclass it, add macros (`SelectOption::macro()`), or register
 * reusable presets (`SelectOption::registerPreset()`).
 */
class SelectOption
{
    use Conditionable;
    use Macroable;

    /**
     * @var array<string, Closure(static): void>
     */
    protected static array $presets = [];

    protected string | Htmlable | Closure | null $label;

    protected bool | Closure $shouldTranslateLabel = false;

    protected string | Htmlable | Closure | null $description = null;

    protected string | BackedEnum | Closure | null $icon = null;

    protected string | Closure | null $iconColor = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $color = null;

    protected string | Htmlable | Closure | null $badgeLabel = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $badgeColor = null;

    protected bool | Closure $isDisabled = false;

    protected bool | Closure $isVisible = true;

    protected bool | Closure $isHidden = false;

    protected string | Closure | null $group = null;

    /**
     * Whether this option was auto-created from a plain `value => label` pair
     * (rather than authored explicitly). Only implicit options are augmented
     * by the component's parallel `descriptions()` / `icons()` maps.
     */
    protected bool $isImplicit = false;

    /**
     * @var array<string> | string | Closure
     */
    protected array | string | Closure $extraClasses = [];

    /**
     * @var array<string, mixed> | Closure
     */
    protected array | Closure $extraAttributes = [];

    /**
     * @param  string | int | BackedEnum  $value  The value persisted in state.
     */
    final public function __construct(protected string | int | BackedEnum $value, string | Htmlable | Closure | null $label = null)
    {
        $this->label = $label;
    }

    public static function make(string | int | BackedEnum $value, string | Htmlable | Closure | null $label = null): static
    {
        return new static($value, $label);
    }

    /**
     * Build an option from an enum case, honouring Filament's enum contracts:
     * {@see HasLabel} for the label, {@see HasIcon} for the icon,
     * {@see HasColor} for the color, and {@see HasDescription} for the
     * description. Anything the case does not implement is simply left unset,
     * so the option falls back to the case name as its label.
     *
     * The result is marked implicit, so the component's parallel
     * `icons()` / `descriptions()` / … maps may still override a value the
     * enum provided.
     */
    public static function fromEnumCase(UnitEnum $case): static
    {
        $value = $case instanceof BackedEnum ? $case->value : $case->name;

        $option = static::make($value)->markImplicit();

        $option->label($case instanceof HasLabel ? ($case->getLabel() ?? $case->name) : $case->name);

        if ($case instanceof HasIcon) {
            $icon = $case->getIcon();

            if (is_string($icon) || $icon instanceof BackedEnum) {
                $option->icon($icon);
            }
        }

        if ($case instanceof HasColor) {
            $option->color($case->getColor());
        }

        if ($case instanceof HasDescription) {
            $option->description($case->getDescription());
        }

        return $option;
    }

    /**
     * Register a reusable preset that any option can apply with `preset()`.
     *
     * ```php
     * SelectOption::registerPreset('archived', fn (SelectOption $option) => $option
     *     ->icon('heroicon-o-archive-box')
     *     ->color('gray')
     *     ->disabled());
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
            "Select option preset [{$name}] is not registered. Register it with " . static::class . '::registerPreset().',
        );

        $preset($this);

        return $this;
    }

    public function value(string | int | BackedEnum $value): static
    {
        $this->value = $value;

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

    /**
     * A secondary line rendered under the label in the dropdown.
     */
    public function description(string | Htmlable | Closure | null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(string | BackedEnum | Closure | null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Color of the leading icon only. Accepts a semantic Filament color name
     * (`success`, `danger`, …) or any CSS color.
     */
    public function iconColor(string | Closure | null $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    /**
     * The option's Filament color — a semantic name (`primary`, `success`, …)
     * or a `Color` palette array. Tints the label and is inherited by the
     * badge unless `badge()` overrides it.
     *
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function color(string | array | Closure | null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * A trailing badge. The color falls back to the option's `color()`.
     *
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function badge(string | Htmlable | Closure | null $label, string | array | Closure | null $color = null): static
    {
        $this->badgeLabel = $label;

        if ($color !== null) {
            $this->badgeColor = $color;
        }

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function badgeColor(string | array | Closure | null $color): static
    {
        $this->badgeColor = $color;

        return $this;
    }

    /**
     * Render the option but prevent it from being selected.
     */
    public function disabled(bool | Closure $condition = true): static
    {
        $this->isDisabled = $condition;

        return $this;
    }

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    /**
     * Remove the option from the list entirely for this render.
     */
    public function hidden(bool | Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * Place the option under an optgroup with the given heading.
     */
    public function group(string | Closure | null $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Additional CSS classes on the option element.
     *
     * @param  array<string> | string | Closure  $classes
     */
    public function classes(array | string | Closure $classes): static
    {
        $this->extraClasses = $classes;

        return $this;
    }

    /**
     * Additional HTML attributes on the option element.
     *
     * @param  array<string, mixed> | Closure  $attributes
     */
    public function extraAttributes(array | Closure $attributes): static
    {
        $this->extraAttributes = $attributes;

        return $this;
    }

    /**
     * Flag this option as auto-generated from a scalar `value => label` pair.
     *
     * @internal Used by {@see SelectOptionCollection::normalize()}.
     */
    public function markImplicit(): static
    {
        $this->isImplicit = true;

        return $this;
    }

    public function isImplicit(): bool
    {
        return $this->isImplicit;
    }

    public function getValue(): string | int | BackedEnum
    {
        return $this->value;
    }

    /**
     * The stringified value as it is stored in and compared against state.
     */
    public function getStringValue(): string
    {
        if ($this->value instanceof BackedEnum) {
            return (string) $this->value->value;
        }

        return (string) $this->value;
    }

    /**
     * Resolve the option against the owning component's evaluation context
     * into a render-ready view model — or `null` when the option is hidden.
     */
    public function resolve(ViewComponent $component): ?OptionViewModel
    {
        $evaluate = fn (mixed $value): mixed => $component->evaluate($value, ['option' => $this]);

        if ($evaluate($this->isHidden) || ! $evaluate($this->isVisible)) {
            return null;
        }

        $label = $this->resolveText($evaluate($this->label));

        if ($label === null) {
            $label = $this->getStringValue();
        }

        if ($evaluate($this->shouldTranslateLabel)) {
            $label = __($label);
        }

        $color = $evaluate($this->color);
        $badgeLabel = $this->resolveText($evaluate($this->badgeLabel));

        return new OptionViewModel(
            value: $this->getStringValue(),
            label: $label,
            description: $this->resolveText($evaluate($this->description)),
            iconHtml: $this->resolveIconHtml($evaluate),
            color: $color,
            badgeLabel: $badgeLabel,
            badgeColor: $evaluate($this->badgeColor) ?? $color,
            isDisabled: (bool) $evaluate($this->isDisabled),
            group: $this->resolveText($evaluate($this->group)),
            extraClasses: $this->resolveClasses($evaluate),
            extraAttributes: $evaluate($this->extraAttributes) ?? [],
        );
    }

    /**
     * Reduce a resolved label/description value to a trimmed plain string, or
     * `null` when blank. `Htmlable` values are flattened to text so a rich
     * label can never smuggle markup past the renderer's escaping.
     */
    protected function resolveText(mixed $value): ?string
    {
        if ($value instanceof Htmlable) {
            $value = trim(strip_tags($value->toHtml()));
        }

        if (blank($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  Closure(mixed): mixed  $evaluate
     * @return array<string>
     */
    protected function resolveClasses(Closure $evaluate): array
    {
        $classes = [];

        foreach ((array) $evaluate($this->extraClasses) as $class) {
            if (filled($class)) {
                $classes[] = (string) $class;
            }
        }

        return $classes;
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
                'color: ' . ColorResolver::toCss($iconColor),
            ]);
        }

        return generate_icon_html($icon, attributes: $attributes, size: IconSize::Small)?->toHtml() ?? '';
    }
}
