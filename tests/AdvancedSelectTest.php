<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOption;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOptionCollection;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\Contact;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    View::share('errors', new ViewErrorBag);
});

/**
 * Mount a select into a real schema container so closures receive the field's
 * evaluation utilities (`$record`, `$state`, `$get`, …).
 */
function mountSelect(AdvancedSelect $select, ?Model $record = null): AdvancedSelect
{
    $schema = Schema::make(new SchemaLivewireComponent);

    if ($record !== null) {
        $schema->record($record);
    }

    $select->container($schema);

    return $select;
}

/**
 * A backed enum that also carries labels, to exercise enum expansion.
 */
enum FakeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}

/**
 * A backed enum implementing the full set of Filament enum contracts, to
 * exercise automatic rich rendering from an enum.
 */
enum RichPriority: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Low = 'low';
    case High = 'high';

    public function getLabel(): string
    {
        return match ($this) {
            self::Low => 'Low priority',
            self::High => 'High priority',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low => 'heroicon-o-arrow-down',
            self::High => 'heroicon-o-arrow-up',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::High => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Low => 'Can wait',
            self::High => 'Needs attention now',
        };
    }
}

/**
 * A label-only enum: the native Select already handles this, so it must stay
 * native.
 */
enum LabelOnlyStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Live = 'live';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}

// ---------------------------------------------------------------------------
// Native compatibility
// ---------------------------------------------------------------------------

it('stays a byte-for-byte native Select for plain options', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        'draft' => 'Draft',
        'published' => 'Published',
    ]));

    expect($select->hasRichOptions())->toBeFalse()
        ->and($select->isNative())->toBeTrue()
        ->and($select->isHtmlAllowed())->toBeFalse()
        ->and($select->getOptions())->toBe(['draft' => 'Draft', 'published' => 'Published']);
});

it('activates rich mode and its native wiring once a SelectOption is present', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft', 'Draft'),
        SelectOption::make('published', 'Published'),
    ]));

    expect($select->hasRichOptions())->toBeTrue()
        ->and($select->isNative())->toBeFalse()
        ->and($select->isHtmlAllowed())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders icon, label, description and badge markup per option', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('published', 'Published')
            ->icon('heroicon-o-globe-alt')
            ->description('Visible to everyone')
            ->color('success')
            ->badge('Live'),
    ]));

    $html = $select->getOptions()['published'];

    expect($html)->toContain('fi-adv-select-option')
        ->and($html)->toContain('fi-adv-select-option-icon')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('Published')
        ->and($html)->toContain('fi-adv-select-option-description')
        ->and($html)->toContain('Visible to everyone')
        ->and($html)->toContain('fi-adv-select-option-badge')
        ->and($html)->toContain('Live')
        ->and($html)->toContain('--fi-adv-select-option-color: var(--color-success-600)');
});

it('escapes developer-supplied label and description text', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('x', '<b>Bold</b>')->description('<i>italic</i>'),
    ]));

    $html = $select->getOptions()['x'];

    expect($html)->toContain('&lt;b&gt;Bold&lt;/b&gt;')
        ->and($html)->not->toContain('<b>Bold</b>')
        ->and($html)->toContain('&lt;i&gt;italic&lt;/i&gt;');
});

it('groups options into optgroups', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft', 'Draft')->group('Private'),
        SelectOption::make('published', 'Published')->group('Public'),
        SelectOption::make('scheduled', 'Scheduled')->group('Public'),
    ]));

    $options = $select->getOptions();

    expect($options)->toHaveKeys(['Private', 'Public'])
        ->and($options['Private'])->toHaveKey('draft')
        ->and($options['Public'])->toHaveKeys(['published', 'scheduled']);
});

// ---------------------------------------------------------------------------
// Parallel maps
// ---------------------------------------------------------------------------

it('augments plain options with parallel icon, description, color and badge maps', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->icons(['draft' => 'heroicon-o-pencil-square'])
            ->descriptions(['draft' => 'Only visible to you'])
            ->optionColors(['published' => 'success'])
            ->optionBadges(['published' => 'Live']),
    );

    $options = $select->getOptions();

    expect($select->hasRichOptions())->toBeTrue()
        ->and($options['draft'])->toContain('Only visible to you')
        ->and($options['draft'])->toContain('<svg')
        ->and($options['published'])->toContain('Live')
        ->and($options['published'])->toContain('var(--color-success-600)');
});

it('never applies parallel maps to explicitly authored options', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options([SelectOption::make('draft', 'Draft')])
            ->descriptions(['draft' => 'Should be ignored']),
    );

    expect($select->getOptions()['draft'])->not->toContain('Should be ignored');
});

// ---------------------------------------------------------------------------
// Lazy evaluation
// ---------------------------------------------------------------------------

it('evaluates every option value lazily with the record injection', function () {
    $select = mountSelect(
        AdvancedSelect::make('plan')->options([
            SelectOption::make('pro', fn (Contact $record): string => $record->name)
                ->description(fn (Contact $record): string => "Owner: {$record->name}")
                ->color(fn (Contact $record): string => $record->name === 'Ada' ? 'success' : 'gray')
                ->badge(fn (Contact $record): string => strtoupper($record->name)),
        ]),
        (new Contact)->forceFill(['id' => 1, 'name' => 'Ada']),
    );

    $html = $select->getOptions()['pro'];

    expect($html)->toContain('Ada')
        ->and($html)->toContain('Owner: Ada')
        ->and($html)->toContain('ADA')
        ->and($html)->toContain('var(--color-success-600)');
});

it('keeps a plain closure options list fully native', function () {
    // A closure cannot be classified at config time, so it stays native
    // (this is what keeps `relationship()` and dynamic value => label lists
    // untouched). Lazy rich options are expressed per-property instead.
    $select = mountSelect(
        AdvancedSelect::make('status')->options(fn (): array => ['draft' => 'Draft']),
    );

    expect($select->hasRichOptions())->toBeFalse()
        ->and($select->isNative())->toBeTrue()
        ->and($select->getOptions())->toBe(['draft' => 'Draft']);
});

it('renders rich options lazily from a static list with per-property closures', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')->options([
            SelectOption::make('draft', fn (): string => 'Computed Draft')
                ->description(fn (): string => 'Computed description'),
        ]),
    );

    expect($select->getOptions()['draft'])->toContain('Computed Draft')
        ->and($select->getOptions()['draft'])->toContain('Computed description');
});

it('drops hidden options and flags disabled ones', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft', 'Draft'),
        SelectOption::make('secret', 'Secret')->hidden(),
        SelectOption::make('locked', 'Locked')->disabled(),
    ]));

    $options = $select->getOptions();

    expect($options)->toHaveKeys(['draft', 'locked'])
        ->and($options)->not->toHaveKey('secret')
        ->and($select->isResolvedOptionDisabled('locked'))->toBeTrue()
        ->and($select->isResolvedOptionDisabled('draft'))->toBeFalse();
});

it('exposes the disabled flag through the native JS option payload', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft', 'Draft'),
        SelectOption::make('locked', 'Locked')->disabled(),
    ]));

    $payload = collect($select->getOptionsForJs())->keyBy('value');

    expect($payload['draft']['isDisabled'])->toBeFalse()
        ->and($payload['locked']['isDisabled'])->toBeTrue()
        ->and($payload['draft']['label'])->toContain('fi-adv-select-option');
});

// ---------------------------------------------------------------------------
// Selected labels
// ---------------------------------------------------------------------------

it('renders a compact selected label without the description', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('published', 'Published')
            ->icon('heroicon-o-globe-alt')
            ->description('Visible to everyone')
            ->badge('Live'),
    ]));

    $label = $select->buildSelectedLabel('published');

    expect($label)->toContain('fi-adv-select-option-selected')
        ->and($label)->toContain('Published')
        ->and($label)->toContain('<svg')
        ->and($label)->toContain('Live')
        ->and($label)->not->toContain('Visible to everyone');
});

it('renders selected labels for a multiple selection', function () {
    $select = mountSelect(AdvancedSelect::make('status')->multiple()->options([
        SelectOption::make('draft', 'Draft'),
        SelectOption::make('published', 'Published'),
    ]));

    $labels = $select->buildSelectedLabels(['draft', 'published']);

    expect($labels)->toHaveKeys(['draft', 'published'])
        ->and($labels['draft'])->toContain('Draft')
        ->and($labels['published'])->toContain('Published');
});

it('returns null for an unknown selected value so the native default wins', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft', 'Draft'),
    ]));

    expect($select->buildSelectedLabel('missing'))->toBeNull();
});

// ---------------------------------------------------------------------------
// Search
// ---------------------------------------------------------------------------

it('filters options by label and description text when searched', function () {
    $select = mountSelect(AdvancedSelect::make('status')->searchable()->options([
        SelectOption::make('draft', 'Draft')->description('Work in progress'),
        SelectOption::make('published', 'Published')->description('Everyone can see it'),
    ]));

    expect($select->getSearchResults('publ'))->toHaveKey('published')
        ->and($select->getSearchResults('publ'))->not->toHaveKey('draft')
        ->and($select->getSearchResults('progress'))->toHaveKey('draft');
});

it('honours the options limit when building search results', function () {
    $options = [];

    for ($i = 0; $i < 10; $i++) {
        $options[] = SelectOption::make("opt-{$i}", "Option {$i}");
    }

    $select = mountSelect(AdvancedSelect::make('status')->searchable()->optionsLimit(3)->options($options));

    expect($select->getSearchResults('Option'))->toHaveCount(3);
});

// ---------------------------------------------------------------------------
// Decorators & custom renderers
// ---------------------------------------------------------------------------

it('applies option and selected-label decorators', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options([SelectOption::make('draft', 'Draft')])
            ->decorateOptionUsing(fn (string $html): string => "<div class=\"wrap-option\">{$html}</div>")
            ->decorateSelectedLabelUsing(fn (string $html): string => "<div class=\"wrap-selected\">{$html}</div>"),
    );

    expect($select->getOptions()['draft'])->toContain('wrap-option')
        ->and($select->buildSelectedLabel('draft'))->toContain('wrap-selected');
});

it('lets a per-instance renderer override the markup', function () {
    $renderer = new class implements RendersOptions
    {
        public function renderOption(OptionViewModel $option, ViewComponent $component): string
        {
            return "<em class=\"custom\">{$option->label}</em>";
        }

        public function renderSelectedLabel(OptionViewModel $option, ViewComponent $component): string
        {
            return "<em class=\"custom-selected\">{$option->label}</em>";
        }
    };

    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options([SelectOption::make('draft', 'Draft')])
            ->renderOptionsUsing($renderer),
    );

    expect($select->getOptions()['draft'])->toBe('<em class="custom">Draft</em>')
        ->and($select->buildSelectedLabel('draft'))->toBe('<em class="custom-selected">Draft</em>');
});

// ---------------------------------------------------------------------------
// Presets
// ---------------------------------------------------------------------------

it('applies a registered option preset', function () {
    SelectOption::registerPreset('archived', fn (SelectOption $option) => $option
        ->color('gray')
        ->badge('Archived')
        ->disabled());

    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('old', 'Old post')->preset('archived'),
    ]));

    expect($select->getOptions()['old'])->toContain('Archived')
        ->and($select->isResolvedOptionDisabled('old'))->toBeTrue();
});

it('throws for an unknown option preset', function () {
    SelectOption::make('x')->preset('does-not-exist');
})->throws(InvalidArgumentException::class);

it('applies a registered component preset', function () {
    AdvancedSelect::registerPreset('statuses', fn (AdvancedSelect $select) => $select
        ->searchable()
        ->options([SelectOption::make('draft', 'Draft')]));

    $select = mountSelect(AdvancedSelect::make('status')->preset('statuses'));

    expect($select->isSearchable())->toBeTrue()
        ->and($select->getOptions())->toHaveKey('draft');
});

// ---------------------------------------------------------------------------
// Enums & value handling
// ---------------------------------------------------------------------------

it('expands a backed enum with labels into rich options via a map', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options(FakeStatus::class)
            ->icons(['draft' => 'heroicon-o-pencil-square']),
    );

    $options = $select->getOptions();

    expect($options)->toHaveKeys(['draft', 'published'])
        ->and($options['draft'])->toContain('Draft')
        ->and($options['draft'])->toContain('<svg')
        ->and($options['published'])->toContain('Published');
});

it('stringifies backed-enum option values', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make(FakeStatus::Published, 'Published'),
    ]));

    expect($select->getOptions())->toHaveKey('published')
        ->and($select->isResolvedOptionDisabled(FakeStatus::Published))->toBeFalse();
});

it('falls back to the value when a label is blank', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options([
        SelectOption::make('draft'),
    ]));

    expect($select->getOptions()['draft'])->toContain('draft');
});

it('appends a single option with option()', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options([SelectOption::make('draft', 'Draft')])
            ->option(SelectOption::make('published', 'Published')),
    );

    expect($select->getOptions())->toHaveKeys(['draft', 'published']);
});

// ---------------------------------------------------------------------------
// Collection unit behaviour
// ---------------------------------------------------------------------------

it('normalizes mixed option inputs into a collection', function () {
    $collection = SelectOptionCollection::normalize([
        'draft' => 'Draft',
        SelectOption::make('published', 'Published'),
        'Group' => ['scheduled' => 'Scheduled'],
    ]);

    expect($collection)->toHaveCount(3)
        ->and($collection->every(fn ($o) => $o instanceof SelectOption))->toBeTrue()
        ->and($collection->last()->getStringValue())->toBe('scheduled');
});

// ---------------------------------------------------------------------------
// Full Livewire / view integration
// ---------------------------------------------------------------------------

it('feeds grouped rich labels into the native JS options payload', function () {
    $payload = mountSelect(
        AdvancedSelect::make('status')->options([
            SelectOption::make('published', 'Published')->icon('heroicon-o-globe-alt')->badge('Live')->group('Public'),
            SelectOption::make('draft', 'Draft')->group('Private'),
        ]),
    )->getOptionsForJs();

    // Grouped payloads are nested: [{ label: group, options: [{label, value}] }].
    $public = collect($payload)->firstWhere('label', 'Public');

    expect($public)->not->toBeNull()
        ->and($public['options'][0]['value'])->toBe('published')
        ->and($public['options'][0]['label'])->toContain('fi-adv-select-option')
        ->and($public['options'][0]['label'])->toContain('Published')
        ->and($public['options'][0]['label'])->toContain('Live');
});

it('caches resolved options and flushes on reconfiguration', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options(['draft' => 'Draft'])
            ->icons(['draft' => 'heroicon-o-pencil-square']),
    );

    $first = $select->getResolvedOptionViewModels();
    $second = $select->getResolvedOptionViewModels();

    expect($first)->toBe($second);

    $select->descriptions(['draft' => 'Now with a description']);

    expect($select->getResolvedOptionViewModels())->not->toBe($first)
        ->and($select->getOptions()['draft'])->toContain('Now with a description');
});

// ---------------------------------------------------------------------------
// Enum contracts
// ---------------------------------------------------------------------------

it('renders an enum implementing the Filament contracts as rich options', function () {
    $select = mountSelect(AdvancedSelect::make('priority')->options(RichPriority::class));

    $options = $select->getOptions();

    expect($select->hasRichOptions())->toBeTrue()
        ->and($select->isNative())->toBeFalse()
        ->and($options)->toHaveKeys(['low', 'high'])
        ->and($options['high'])->toContain('High priority')
        ->and($options['high'])->toContain('Needs attention now')
        ->and($options['high'])->toContain('<svg')
        ->and($options['high'])->toContain('var(--color-danger-600)')
        ->and($options['low'])->toContain('Low priority')
        ->and($options['low'])->toContain('Can wait');
});

it('keeps a label-only enum fully native', function () {
    $select = mountSelect(AdvancedSelect::make('status')->options(LabelOnlyStatus::class));

    expect($select->hasRichOptions())->toBeFalse()
        ->and($select->isNative())->toBeTrue()
        ->and($select->getOptions())->toBe(['draft' => 'Draft', 'live' => 'Live']);
});

it('preserves native enum state casting for a rich enum', function () {
    $select = mountSelect(AdvancedSelect::make('priority')->options(RichPriority::class));

    // The enum must still be registered so selected values hydrate as enum
    // instances, exactly as on a native Select.
    expect($select->getEnum())->toBe(RichPriority::class)
        ->and($select->getEnumDefaultStateCast())->not->toBeNull();
});

it('renders a compact selected label from an enum instance', function () {
    $select = mountSelect(AdvancedSelect::make('priority')->options(RichPriority::class));

    $label = $select->buildSelectedLabel(RichPriority::High);

    expect($label)->toContain('High priority')
        ->and($label)->toContain('<svg')
        ->and($label)->not->toContain('Needs attention now');
});

it('lets a parallel map override an enum-provided value', function () {
    $select = mountSelect(
        AdvancedSelect::make('priority')
            ->options(RichPriority::class)
            ->descriptions(['high' => 'Escalated']),
    );

    $high = $select->getOptions()['high'];

    // The description is overridden; the enum's icon and color remain.
    expect($high)->toContain('Escalated')
        ->and($high)->not->toContain('Needs attention now')
        ->and($high)->toContain('var(--color-danger-600)');
});

it('activates rich enum rendering when a parallel map is added to a label-only enum', function () {
    $select = mountSelect(
        AdvancedSelect::make('status')
            ->options(LabelOnlyStatus::class)
            ->icons(['draft' => 'heroicon-o-pencil-square']),
    );

    expect($select->hasRichOptions())->toBeTrue()
        ->and($select->getOptions()['draft'])->toContain('<svg')
        ->and($select->getOptions()['draft'])->toContain('Draft');
});
