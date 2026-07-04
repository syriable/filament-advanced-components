<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns;

use BackedEnum;
use Closure;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\HasBadge;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOption;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOptionCollection;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Rendering\OptionRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;
use UnitEnum;

/**
 * The heart of {@see AdvancedSelect}:
 * turns rich {@see SelectOption} definitions into markup and feeds them back
 * into Filament's native `Select` through its own public extension points.
 *
 * ## Pipeline
 *
 * ```
 * raw config ──▶ evaluate ──▶ resolve (view models) ──▶ render ──▶ native Select
 * ```
 *
 * Rich mode stays dormant until an advanced feature is actually used — a
 * `SelectOption` object, or one of the parallel `descriptions()` / `icons()` /
 * `optionColors()` / `optionBadges()` maps. Until then the component behaves
 * exactly like a first-party `Select` (native `<select>`, native search, no
 * HTML). The moment rich mode activates it wires, once:
 *
 *  - `options()`            → the rendered `value => html` dropdown map;
 *  - `getOptionLabelUsing()`/`getOptionLabelsUsing()` → the compact selected labels;
 *  - `getSearchResultsUsing()` → plain-text filtering over the resolved options;
 *  - `disableOptionWhen()`  → per-option disabled state;
 *  - `allowHtml()` + `native(false)` → so the JS select can render the markup.
 *
 * Resolved view models are memoized per instance and reused across every one
 * of those hooks within a request; any configuration change flushes the cache.
 */
trait HasRichOptions
{
    /**
     * The raw options input, in any shape `Select::options()` accepts, plus
     * lists of {@see SelectOption} objects.
     *
     * @var array<mixed> | Arrayable<array-key, mixed> | string | Closure | null
     */
    protected array | Arrayable | string | Closure | null $rawOptions = null;

    /** @var array<string, mixed> | Closure | null */
    protected array | Closure | null $optionDescriptions = null;

    /** @var array<string, mixed> | Closure | null */
    protected array | Closure | null $optionIcons = null;

    /** @var array<string, mixed> | Closure | null */
    protected array | Closure | null $optionColors = null;

    /** @var array<string, mixed> | Closure | null */
    protected array | Closure | null $optionBadges = null;

    protected bool $hasRichOptions = false;

    protected bool $richOptionsWired = false;

    protected ?RendersOptions $optionRenderer = null;

    /** @var array<OptionViewModel> | null */
    protected ?array $resolvedOptionViewModels = null;

    /** @var array<string, OptionViewModel> | null */
    protected ?array $optionViewModelIndex = null;

    /**
     * Define the options. Accepts everything the native `Select` does — a
     * `value => label` map, a grouped array, an enum class, a closure — and,
     * additionally, a list of {@see SelectOption} objects (or a mix). Passing
     * any `SelectOption`, or an enum whose cases carry an icon, color, or
     * description (via Filament's {@see HasIcon} / {@see HasColor} /
     * {@see HasDescription} contracts), activates rich rendering; anything
     * else stays fully native unless a parallel map (below) is also set.
     *
     * @param  array<mixed> | Arrayable<array-key, mixed> | string | Closure | null  $options
     */
    public function options(array | Arrayable | string | Closure | null $options): static
    {
        $this->rawOptions = $options;
        $this->flushOptionCache();

        // Register the enum for state casting exactly as the native Select
        // does, so selected values still hydrate/dehydrate as enum instances
        // whether or not rich rendering is active.
        if (is_string($options) && enum_exists($options)) {
            $this->enum($options);
        }

        if ($this->richOptionsWired || $this->arrayContainsRichOptions($options) || $this->isRichEnum($options)) {
            // Once rich, the native options getter must keep pointing at the
            // rendering closure (which reads the fresh {@see $rawOptions}) —
            // never at the raw array, which would bypass rendering.
            $this->activateRichOptions();
        } else {
            // Keep the native code path authoritative until (and unless) rich
            // mode is needed, so a plain AdvancedSelect is byte-for-byte a
            // Select — this also leaves `relationship()`, label-only enums,
            // and plain option arrays fully native.
            parent::options($options);
        }

        return $this;
    }

    /**
     * Attach a single {@see SelectOption}, activating rich rendering.
     */
    public function option(SelectOption $option): static
    {
        $existing = is_array($this->rawOptions) ? $this->rawOptions : [];
        $existing[] = $option;

        return $this->options($existing);
    }

    /**
     * A secondary line per option value, applied to plain `value => label`
     * options. Explicitly authored {@see SelectOption} objects are untouched.
     *
     * @param  array<string, mixed> | Closure  $descriptions
     */
    public function descriptions(array | Closure $descriptions): static
    {
        $this->optionDescriptions = $descriptions;
        $this->flushOptionCache();
        $this->activateRichOptions();

        return $this;
    }

    /**
     * A leading icon per option value, applied to plain `value => label`
     * options.
     *
     * @param  array<string, mixed> | Closure  $icons
     */
    public function icons(array | Closure $icons): static
    {
        $this->optionIcons = $icons;
        $this->flushOptionCache();
        $this->activateRichOptions();

        return $this;
    }

    /**
     * A Filament color per option value, applied to plain `value => label`
     * options.
     *
     * @param  array<string, mixed> | Closure  $colors
     */
    public function optionColors(array | Closure $colors): static
    {
        $this->optionColors = $colors;
        $this->flushOptionCache();
        $this->activateRichOptions();

        return $this;
    }

    /**
     * A trailing badge per option value, applied to plain `value => label`
     * options.
     *
     * @param  array<string, mixed> | Closure  $badges
     */
    public function optionBadges(array | Closure $badges): static
    {
        $this->optionBadges = $badges;
        $this->flushOptionCache();
        $this->activateRichOptions();

        return $this;
    }

    public function hasRichOptions(): bool
    {
        return $this->hasRichOptions;
    }

    /**
     * Swap the option renderer for this instance only, overriding the
     * container-bound {@see RendersOptions} default.
     */
    public function renderOptionsUsing(RendersOptions $renderer): static
    {
        $this->optionRenderer = $renderer;

        return $this;
    }

    public function getOptionRenderer(): RendersOptions
    {
        return $this->optionRenderer ??= app()->bound(RendersOptions::class)
            ? app(RendersOptions::class)
            : app(OptionRenderer::class);
    }

    /**
     * Discard memoized resolutions after a configuration change.
     */
    public function flushOptionCache(): static
    {
        $this->resolvedOptionViewModels = null;
        $this->optionViewModelIndex = null;

        return $this;
    }

    /**
     * Wire the native `Select` extension points to the rich pipeline — once.
     */
    protected function activateRichOptions(): void
    {
        $this->hasRichOptions = true;

        if ($this->richOptionsWired) {
            return;
        }

        $this->richOptionsWired = true;

        parent::options(fn (): array => $this->buildDropdownOptions());
        $this->allowHtml();
        $this->native(false);
        $this->getOptionLabelUsing(fn (mixed $value): ?string => $this->buildSelectedLabel($value));
        $this->getOptionLabelsUsing(fn (mixed $values): array => $this->buildSelectedLabels($values));
        $this->getSearchResultsUsing(fn (string $search): array => $this->buildSearchResults($search));
    }

    /**
     * Fold a per-option `disabled()` into the native disabled check so it
     * composes with — and cannot be clobbered by — any `disableOptionWhen()`
     * the developer also registers on the field.
     *
     * @param  array-key  $value
     */
    public function isOptionDisabled($value, string | Htmlable $label): bool
    {
        if ($this->hasRichOptions() && $this->isResolvedOptionDisabled($value)) {
            return true;
        }

        return parent::isOptionDisabled($value, $label);
    }

    public function hasDisabledOptions(): bool
    {
        if ($this->hasRichOptions() && $this->hasResolvedDisabledOptions()) {
            return true;
        }

        return parent::hasDisabledOptions();
    }

    /**
     * Whether any resolved option is disabled for this render — so validation
     * ({@see getEnabledOptions()}) knows to exclude it.
     */
    protected function hasResolvedDisabledOptions(): bool
    {
        foreach ($this->getResolvedOptionViewModels() as $viewModel) {
            if ($viewModel->isDisabled) {
                return true;
            }
        }

        return false;
    }

    /**
     * The rendered `value => html` (or `group => [value => html]`) map handed
     * to the native dropdown.
     *
     * @return array<string, string | array<string, string>>
     */
    public function buildDropdownOptions(): array
    {
        $renderer = $this->getOptionRenderer();
        $result = [];

        foreach ($this->getResolvedOptionViewModels() as $viewModel) {
            $html = $this->applyOptionDecorators($renderer->renderOption($viewModel, $this), $viewModel);

            if ($viewModel->group !== null) {
                $result[$viewModel->group][$viewModel->value] = $html;
            } else {
                $result[$viewModel->value] = $html;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string | array<string, string>>
     */
    public function buildSearchResults(string $search): array
    {
        $search = trim($search);
        $renderer = $this->getOptionRenderer();
        $limit = $this->getOptionsLimit();

        $result = [];
        $count = 0;

        foreach ($this->getResolvedOptionViewModels() as $viewModel) {
            if ($search !== '' && mb_stripos($viewModel->searchableText(), $search) === false) {
                continue;
            }

            $html = $this->applyOptionDecorators($renderer->renderOption($viewModel, $this), $viewModel);

            if ($viewModel->group !== null) {
                $result[$viewModel->group][$viewModel->value] = $html;
            } else {
                $result[$viewModel->value] = $html;
            }

            if (++$count >= $limit) {
                break;
            }
        }

        return $result;
    }

    public function buildSelectedLabel(mixed $value): ?string
    {
        $viewModel = $this->getOptionViewModelIndex()[$this->stringifyStateValue($value)] ?? null;

        if ($viewModel === null) {
            return null;
        }

        return $this->applySelectedLabelDecorators(
            $this->getOptionRenderer()->renderSelectedLabel($viewModel, $this),
            $viewModel,
        );
    }

    /**
     * @return array<string, string>
     */
    public function buildSelectedLabels(mixed $values): array
    {
        $index = $this->getOptionViewModelIndex();
        $renderer = $this->getOptionRenderer();
        $labels = [];

        foreach ((array) ($values ?? []) as $value) {
            $value = $this->stringifyStateValue($value);

            if ($viewModel = $index[$value] ?? null) {
                $labels[$value] = $this->applySelectedLabelDecorators(
                    $renderer->renderSelectedLabel($viewModel, $this),
                    $viewModel,
                );
            }
        }

        return $labels;
    }

    public function isResolvedOptionDisabled(mixed $value): bool
    {
        $viewModel = $this->getOptionViewModelIndex()[$this->stringifyStateValue($value)] ?? null;

        return $viewModel !== null && $viewModel->isDisabled;
    }

    /**
     * Reduce a state value to the string key options are indexed by, handling
     * both backed and pure enum instances the way Filament stores them.
     */
    protected function stringifyStateValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return (string) $value;
    }

    /**
     * The resolved, render-ready options — memoized for the instance and
     * shared across every native hook within a request.
     *
     * @return array<OptionViewModel>
     */
    public function getResolvedOptionViewModels(): array
    {
        return $this->resolvedOptionViewModels ??= $this->resolveOptionCollection()->resolveFor($this);
    }

    /**
     * @return array<string, OptionViewModel>
     */
    protected function getOptionViewModelIndex(): array
    {
        if ($this->optionViewModelIndex !== null) {
            return $this->optionViewModelIndex;
        }

        $index = [];

        foreach ($this->getResolvedOptionViewModels() as $viewModel) {
            // First declaration wins, mirroring the native label lookup.
            $index[$viewModel->value] ??= $viewModel;
        }

        return $this->optionViewModelIndex = $index;
    }

    /**
     * Normalize the raw options into {@see SelectOption} objects and augment
     * the implicit ones with the parallel maps.
     */
    protected function resolveOptionCollection(): SelectOptionCollection
    {
        $collection = SelectOptionCollection::normalize($this->evaluateRawOptions());

        $this->applyOptionMaps($collection);

        return $collection;
    }

    /**
     * Evaluate {@see $rawOptions} the same way the native `Select` evaluates
     * its own — closures run, `Arrayable` flattens — without routing back
     * through the (now overridden) options getter. An enum is expanded into a
     * list of {@see SelectOption} objects via its Filament contracts, so it
     * flows through {@see SelectOptionCollection::normalize()} like any other.
     *
     * @return array<mixed>
     */
    protected function evaluateRawOptions(): array
    {
        $options = $this->evaluate($this->rawOptions) ?? $this->getEnum() ?? [];

        if (is_string($options) && enum_exists($options)) {
            return SelectOptionCollection::fromEnum($options)->all();
        }

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        return is_array($options) ? $options : [];
    }

    /**
     * Whether the given options input is an enum whose cases carry rendering
     * information beyond a label — an icon, color, description, or badge — and
     * so warrant rich rendering. A label-only ({@see HasLabel}) or plain enum
     * stays native, since the native `Select` already renders those.
     */
    protected function isRichEnum(mixed $options): bool
    {
        if (! is_string($options) || ! enum_exists($options)) {
            return false;
        }

        return is_a($options, HasIcon::class, allow_string: true)
            || is_a($options, HasColor::class, allow_string: true)
            || is_a($options, HasDescription::class, allow_string: true)
            || is_a($options, HasBadge::class, allow_string: true);
    }

    protected function applyOptionMaps(SelectOptionCollection $collection): void
    {
        $descriptions = $this->evaluate($this->optionDescriptions);
        $icons = $this->evaluate($this->optionIcons);
        $colors = $this->evaluate($this->optionColors);
        $badges = $this->evaluate($this->optionBadges);

        if (blank($descriptions) && blank($icons) && blank($colors) && blank($badges)) {
            return;
        }

        foreach ($collection as $option) {
            if (! $option->isImplicit()) {
                continue;
            }

            $value = $option->getStringValue();

            if (is_array($icons) && filled($icons[$value] ?? null)) {
                $option->icon($icons[$value]);
            }

            if (is_array($descriptions) && filled($descriptions[$value] ?? null)) {
                $option->description($descriptions[$value]);
            }

            if (is_array($colors) && filled($colors[$value] ?? null)) {
                $option->color($colors[$value]);
            }

            if (is_array($badges) && filled($badges[$value] ?? null)) {
                $option->badge($badges[$value]);
            }
        }
    }

    /**
     * Whether an options input carries at least one {@see SelectOption} —
     * including inside a nested group array, e.g.
     * `['Group' => [SelectOption::make(...)]]`, so a grouped rich list still
     * activates rich rendering instead of leaking raw option objects into the
     * native path.
     */
    protected function arrayContainsRichOptions(mixed $options): bool
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (! is_array($options)) {
            return false;
        }

        foreach ($options as $option) {
            if ($option instanceof SelectOption) {
                return true;
            }

            if ((is_array($option) || $option instanceof Arrayable) && $this->arrayContainsRichOptions($option)) {
                return true;
            }
        }

        return false;
    }
}
