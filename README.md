# This is my package filament-advanced-components

[![Latest Version on Packagist](https://img.shields.io/packagist/v/syriable/filament-advanced-components.svg?style=flat-square)](https://packagist.org/packages/syriable/filament-advanced-components)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/syriable/filament-advanced-components/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/syriable/filament-advanced-components/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/syriable/filament-advanced-components/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/syriable/filament-advanced-components/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/syriable/filament-advanced-components.svg?style=flat-square)](https://packagist.org/packages/syriable/filament-advanced-components)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require syriable/filament-advanced-components
```

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file or your app's css file if using the standalone packages.

```css
@source '../../../../vendor/syriable/filament-advanced-components/resources/**/*.blade.php';
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-advanced-components-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-advanced-components-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-advanced-components-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

### MultiProgressColumn, MultiProgressEntry & MultiProgressField

A single progress bar divided into multiple colored segments — like the per-language progress
bars on translation dashboards such as Crowdin or Lokalise — available as a table column, an
infolist entry, and a read-only form field, all with an identical configuration API.

```
|███████████▓▓▓▓░░░░░░|  70% · 600 keys
```

**What it looks like:**

- _Light mode, default:_ a slim pill-shaped bar inside the table cell. Green (Translated),
  amber (Needs Review), and red (Missing) blocks sit flush against each other, with the overall
  percentage in small gray tabular figures on the right. Hovering a block raises a Filament
  tooltip reading "**Translated** / 420 keys / 70%".
- _Dark mode:_ the track becomes a translucent light-gray wash and each segment automatically
  switches to a shade with at least 3:1 (WCAG AA non-text) contrast against the dark surface.
- _With legend:_ below the bar, a row of colored dots with labels, percentages, and
  optional count badges. Use `showLegendFrom('md')` to hide it on small screens and keep
  table rows compact.
- _Striped / gradient:_ diagonal translucent stripes, or a subtle left-to-right lightening
  gradient per segment.
- _Empty / loading:_ an empty track with optional placeholder text, or a pulsing skeleton bar.

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgressColumn;

MultiProgressColumn::make('translation_progress')
    ->segments(fn ($record) => [
        ['label' => 'Translated', 'value' => 70, 'color' => 'success'],
        ['label' => 'Needs Review', 'value' => 20, 'color' => 'warning'],
        ['label' => 'Missing', 'value' => 10, 'color' => 'danger'],
    ]);
```

Values are normalized automatically: pass percentages, raw counts, or anything else — each
segment's width is its share of the total. Provide an explicit denominator with `total()` and
any shortfall renders as empty track:

```php
MultiProgressColumn::make('translation_progress')
    ->segments(fn (Language $record): array => [
        ['label' => 'Translated', 'value' => $record->translated_count, 'color' => 'success'],
        ['label' => 'Needs Review', 'value' => $record->review_count, 'color' => 'warning'],
    ])
    ->total(fn (Language $record): int => $record->keys_count)
    ->valueSuffix('keys')
    ->showPercentage()
    ->showTotal()
    ->showLegend();
```

Because rendering is fully server-side Blade, the bar updates automatically with Livewire —
polling, actions, and table refreshes just work, and `animated()` (on by default) transitions
the widths smoothly.

#### Infolists

The same bar is available as an infolist entry, `MultiProgressEntry`, with an
identical configuration API — swap the class name to move a bar between a
table and an infolist:

```php
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\MultiProgressEntry;

MultiProgressEntry::make('translation_progress')
    ->segments(fn (Language $record): array => [
        ['label' => 'Translated', 'value' => $record->translated_count, 'color' => 'success'],
        ['label' => 'Needs Review', 'value' => $record->review_count, 'color' => 'warning'],
    ])
    ->total(fn (Language $record): int => $record->keys_count)
    ->valueSuffix('keys')
    ->showPercentage()
    ->showLegend();
```

The only naming difference: segment spacing is `segmentGap()` (on all
components), because infolist entries and form fields inherit Filament's
schema-level `gap()` toggle. The table column additionally accepts `gap()`
as an alias.

#### Forms

`MultiProgressField` is the same bar as a real form field — with the standard
field wrapper (label, helper text, hint, validation slot) around it:

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\MultiProgressField;

MultiProgressField::make('translation_progress')
    ->label('Translation progress')
    ->helperText('Updated automatically as the AI translation job runs.')
    ->segments(fn (Language $record): array => [
        ['label' => 'Translated', 'value' => $record->translated_count, 'color' => 'success'],
        ['label' => 'Needs Review', 'value' => $record->review_count, 'color' => 'warning'],
    ])
    ->total(fn (Language $record): int => $record->keys_count)
    ->showPercentage();
```

It is display-only — `dehydrated(false)` by default — so it never writes
anything back on submit. Being a `Field`, though, it participates in form
state: without a `segments()` closure it reads its own state path (so a model
attribute or JSON cast holding a segments array hydrates it with zero
configuration), and a closure receives `$get` to recompute the bar live as
other fields change:

```php
MultiProgressField::make('budget_allocation')
    ->segments(fn (Get $get): array => [
        ['label' => 'Marketing', 'value' => (int) $get('marketing_budget'), 'color' => 'info'],
        ['label' => 'Engineering', 'value' => (int) $get('engineering_budget'), 'color' => 'success'],
    ])
    ->total(fn (Get $get): int => (int) $get('total_budget'));
```

Pair it with `->live()` on the source inputs and the bar re-balances as the
user types.

#### Segment definition

Each segment is an array (or a `Segment` object) with these keys:

| Key                     | Type                 | Description                                                   |
| ----------------------- | -------------------- | ------------------------------------------------------------- |
| `label`                 | `string`             | Used in tooltips, the legend, and ARIA labels.                |
| `value`                 | `int\|float`         | Raw amount or percentage; normalized against the total.       |
| `color`                 | `string\|array`      | Semantic name, raw CSS color, or Filament palette (optional). |
| `tooltip`               | `string\|Htmlable`   | Overrides the generated tooltip (optional).                   |
| `icon`                  | `string\|BackedEnum` | Shown in the legend entry (optional).                         |
| `badge`                 | `string`             | Small badge next to the legend entry (optional).              |
| `url`                   | `string`             | Makes the segment a clickable link (optional).                |
| `shouldOpenUrlInNewTab` | `bool`               | Defaults to `false`.                                          |

The fluent alternative:

```php
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Segment;

->segments(fn ($record) => [
    Segment::make('Translated')
        ->value($record->translated_count)
        ->color('success')
        ->icon('heroicon-m-language')
        ->badge((string) $record->translated_count)
        ->url(route('translations.index', $record)),
])
```

If you call neither `segments()` nor pass a closure, the column falls back to its own state —
so an Eloquent accessor (or JSON-cast attribute) returning a segments array works with zero
configuration.

The column cell also links like any other column — a `MultiProgressColumn::make(...)->url(...)`
or a table `recordUrl` / `recordAction` makes the whole bar clickable. When both the cell and a
segment carry a link, the segment can't be a real `<a>` nested inside the cell's anchor (invalid
HTML), so a clickable segment automatically degrades to a keyboard-accessible `role="link"`
element that navigates via script and stops the click from also triggering the cell link — the
same handling as `AdvancedTextColumn`'s badges. (Call `->disabledClick()` to opt back into a
fully read-only cell.)

#### Colors

Three formats are accepted per segment:

- **Semantic names** — `success`, `warning`, `danger`, `info`, `primary`, `gray`, or any custom
  color registered with `FilamentColor`. These resolve through Filament's contrast-aware color
  maps, picking a shade with WCAG AA non-text contrast (3:1) against the track in _both_ light
  and dark mode.
- **Full palettes** — e.g. `Color::Purple` or `Color::hex('#8b5cf6')` from
  `Filament\Support\Colors\Color`. Same contrast-aware resolution, inlined as CSS custom
  properties.
- **Raw CSS colors** — `#0ea5e9`, `rgb(...)`, `oklch(...)`, `var(--my-brand)`. Used verbatim in
  both themes.

Segments without a color cycle through `fallbackColors()`
(default: primary → success → warning → danger → info → gray).

#### Tooltips

Enabled by default. Each segment shows its label, formatted value, and percentage using
Filament's tooltip system (tippy.js via the `x-tooltip` directive — the only place Alpine is
used, and only rendered when a tooltip exists).

```php
->segmentTooltips(false)                       // disable entirely
->valueSuffix('keys')                          // "420 keys"
->formatValueUsing(fn ($state) => ...)         // custom value formatting
->formatPercentageUsing(fn ($state) => ...)    // custom percentage formatting
->formatSegmentTooltipUsing(                   // fully custom tooltip
    fn (array $segment) => "{$segment['label']}: {$segment['formattedValue']}"
)
```

A per-segment `tooltip` key always wins over the generated one.

#### Appearance

```php
->size('sm')              // xs | sm | md (default) | lg | xl
->height(14)              // explicit height: px int or any CSS length string
->segmentGap(2)           // pixels between segments (alias: gap() on the column)
->borderRadius(4)         // px int or CSS value; default is fully rounded
->squared()               // shorthand for zero radius
->animated(false)         // width transitions (on by default, respects reduced motion)
->striped()               // diagonal stripe overlay
->gradient()              // subtle per-segment gradient
->hoverEffect()           // brightness lift on hover
->compact()               // thinner bar, tighter typography, no legend
->minSegmentWidth(2)      // tiny segments stay ≥ 2% wide; larger ones shrink to fit
```

#### Labels, legend & responsive visibility

Show the overall percentage, total count, and/or a per-segment legend next to or below the
bar:

```php
->showPercentage()   // "70%" to the right of the bar
->showTotal()        // "· 600 keys" after the percentage (pair with valueSuffix())
->showLegend()       // colored dots + labels below the bar
```

On table columns, hide these on small screens so the cell stays a single slim bar — the same
breakpoint API Filament uses for `visibleFrom()` / `hiddenFrom()` on columns (`sm`, `md`, `lg`,
`xl`, `2xl`):

```php
MultiProgressColumn::make('translation_progress')
    ->segments(fn (Language $record): array => [/* … */])
    ->total(fn (Language $record): int => $record->keys_count)
    ->valueSuffix('keys')
    ->showPercentageFrom('md')   // hidden below md, visible from md up
    ->showTotalFrom('md')
    ->showLegendFrom('md');

// Or hide from a breakpoint upward:
->hidePercentageFrom('lg')
->hideTotalFrom('lg')
->hideLegendFrom('lg')
```

Each `show*From()` call also enables that feature (like calling `showPercentage()`). The
percentage, total, and legend can use different breakpoints independently. Segment tooltips
remain available on all screen sizes when enabled.

`compact()` still suppresses the below-bar legend entirely; use `showLegendFrom()` when you
want a legend on larger screens only.

#### Empty & loading states

```php
->placeholder('No data yet')   // Filament's standard placeholder, shown next to an empty track
->skeleton()                   // pulsing skeleton bar while segments are empty
```

#### Accessibility

- The bar exposes a single screen-reader summary ("Translated: 70%, Needs Review: 20%, …")
  instead of a soup of unlabeled colored boxes.
- Segments with tooltips are keyboard-focusable (`tabindex="0"`) with matching `aria-label`s,
  so the tooltip is reachable without a mouse; purely decorative segments are `aria-hidden`.
- Clickable segments are real `<a>` elements with descriptive labels.
- Segment shades are chosen for WCAG AA non-text contrast against the track in both themes.
- All animations are disabled under `prefers-reduced-motion: reduce`.

#### Performance

All normalization, color resolution, and tooltip generation happens once per cell in
`getProgressData()`; the Blade view only prints precomputed values. Filament caches resolved
color classes per palette, so tables with thousands of rows do no repeated color math. The
column's stylesheet is registered through `FilamentAsset` and works in any panel without a
custom theme.

#### Live updates (event-driven, no polling)

Because the bar is plain server-rendered Blade, it updates with _anything_ that re-renders
the Livewire component — including a broadcast event. That means you can watch a long-running
job (say, AI-powered automatic translation) creep forward in real time **without**
`wire:poll` hammering your server every few seconds whether or not anything changed.

The pattern: the job broadcasts an event over WebSockets only when progress actually moves,
the Filament page captures it and refreshes itself, and `animated()` (on by default)
transitions the segments smoothly to their new widths. While the job is idle, the network is
completely silent — one open WebSocket connection, zero requests.

**1. Install Laravel's first-party WebSocket server ([Reverb](https://laravel.com/docs/reverb)):**

```bash
php artisan install:broadcasting   # installs Reverb + Laravel Echo
php artisan reverb:start           # run alongside your queue worker
```

**2. Broadcast from the job — but only on meaningful change.** Throttling on the server is
what keeps this cheap: guard the broadcast so a 2,000-key run emits at most ~100 tiny events
instead of one per key.

```php
class TranslationProgressUpdated implements ShouldBroadcast
{
    public function __construct(public Language $language) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("project.{$this->language->project_id}.translations")];
    }
}
```

```php
// Inside the job's loop:
$percent = (int) floor($language->progressPercent());

if ($percent !== $lastBroadcastPercent) {
    broadcast(new TranslationProgressUpdated($language));
    $lastBroadcastPercent = $percent;
}
```

**3. Capture the event on the Filament page.** Pages are Livewire components, so map the
Echo event to Livewire's built-in `$refresh` action:

```php
class ListLanguages extends ListRecords
{
    protected function getListeners(): array
    {
        return [
            "echo-private:project.{$this->projectId}.translations,TranslationProgressUpdated" => '$refresh',
        ];
    }
}
```

The same listener works on a View page rendering a `MultiProgressEntry`. Filament boots Echo
automatically once your Reverb credentials are in the `broadcasting.echo` section of
`config/filament.php` (publish it with
`php artisan vendor:publish --tag=filament-config`).

That's the whole loop: job progresses → one small WebSocket frame → Livewire re-renders →
the segments animate to their new widths.

> [!TIP]
> Can't run a WebSocket process where you deploy? The lightweight fallback is _conditional_
> polling — poll only while a job is actually running, and go silent otherwise:
>
> ```php
> ->poll(fn (): ?string => TranslationRun::active()->exists() ? '3s' : null)
> ```

### AdvancedTextColumn & AdvancedTextEntry

Drop-in replacements for Filament's `TextColumn` (tables) and `TextEntry` (infolists) with
advanced ergonomics: text masking, contact links, affix images and icons, extra typography,
a character count, and a rendering decorator pipeline. Every native feature —
`searchable()`, `sortable()`, `badge()`, `copyable()`, `limit()`, `dateTime()`, `money()`,
descriptions, placeholders — keeps working untouched, because the native markup is rendered
by the parent itself and only _wrapped_ when an advanced feature needs it.

Both components share one configuration API (the `HasAdvancedText` concern), so a
configuration moves between a table and an infolist by swapping the class name.

```php
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

AdvancedTextColumn::make('email')
    ->searchable()
    ->sortable()
    ->copyable()
    ->mailable()
    ->bold(fn (User $record): bool => $record->is_admin)
    ->prefixImage(fn (User $record): string => $record->avatar_url)
    ->imageCircular();
```

Every option accepts a static value **or a closure** with Filament's usual `$record`,
`$state`, `$livewire`, `$table`, and `$rowLoop` injections, evaluated lazily per cell.

#### Infolists

The same component is available as an infolist entry with an identical API. Decorations are
injected inside the entry wrapper, so the label, hint, and helper text stay untouched:

```php
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\AdvancedTextEntry;

AdvancedTextEntry::make('email')
    ->copyable()
    ->mailable()
    ->maskEmail(fn (): bool => auth()->user()->cannot('viewSensitiveData'))
    ->prefixIcon(Heroicon::Envelope);
```

Everything documented below applies to both classes.

#### Masking

Hide sensitive values without giving up searching or sorting on the raw column:

```php
AdvancedTextColumn::make('phone')
    ->callable()
    ->masked(fn (): bool => auth()->user()->cannot('viewSensitiveData'))
    ->maskIndex(3)      // zero-based; negative counts from the end
    ->maskLength(5)     // null (default) masks through to the end
    ->maskCharacter('*');

AdvancedTextColumn::make('email')
    ->maskEmail();      // jane.doe@example.com → j•••••••@example.com

AdvancedTextColumn::make('api_token')
    ->maskStateUsing(fn (string $state): string => Str::mask($state, '#', 4)); // full control
```

Masking is applied **after** native formatting, so `formatStateUsing()`, `dateTime()`,
`money()`, and `limit()` all see the raw value and the mask operates on exactly what would
otherwise reach the browser. It is also leak-proof by design:

- `copyable()` copies the **masked** value unless you explicitly set `copyableState()`,
- masked cells never emit generated contact links (the raw state would be readable in the
  `href`),
- `fullStateTooltip()` is suppressed while the mask is active,
- HTML states (`html()` / `markdown()`) are reduced to plain text before masking, so a
  partial mask can't leak markup.

#### Contact links

Turn the cell into a link generated from its own state. Invalid states (a malformed email,
a number without digits) degrade gracefully to plain text, and an explicit `url()` always
wins:

```php
AdvancedTextColumn::make('email')->mailable();                       // mailto:
AdvancedTextColumn::make('phone')->callable();                       // tel: (separators stripped)
AdvancedTextColumn::make('phone')->whatsappable(message: 'Hello!');  // https://wa.me/…?text=…
```

Each helper accepts a condition: `->mailable(fn ($record) => $record->email_verified)`.

#### Affix images & icons

Render an image or icon on either side of the content — including a prefix _and_ a suffix
at the same time, which the native single `icon()` cannot do:

```php
AdvancedTextColumn::make('name')
    ->prefixImage(fn (User $record): string => $record->avatar_url)
    ->imageCircular()             // or ->imageRounded(4) / ->imageRounded('0.5rem')
    ->imageSize('2rem')           // integers are pixels, strings any CSS length
    ->imageFit('cover')           // any CSS object-fit value; defaults to 'contain'
    ->imageAlt('Avatar')          // defaults to '' (decorative)
    ->suffixIcon(Heroicon::CheckBadge, color: 'success')
    ->prefixIcon(Heroicon::User);
```

`suffixImage()` mirrors `prefixImage()`. Images are lazy-loaded and their attributes are
escaped.

#### Typography

```php
AdvancedTextColumn::make('name')
    ->bold(fn (User $record): bool => $record->is_admin)
    ->italic()
    ->underline()
    ->strikethrough(fn (Task $record): bool => $record->is_done)
    ->uppercase();                // also: lowercase(), capitalize()
```

These compose with the native `weight()`, `fontFamily()`, `size()`, and `color()` — use
the native APIs where they exist; the modifiers above only add what Filament doesn't have.

#### Character count

```php
AdvancedTextColumn::make('bio')->characterCount();              // “142”
AdvancedTextColumn::make('bio')->characterLimitIndicator(160);  // “142 / 160”, red when over
```

The count measures the raw state (multibyte-safe), so it stays truthful next to `limit()`
or `masked()`.

#### Tooltip & copy enhancements

```php
AdvancedTextColumn::make('description')
    ->limit(30)
    ->fullStateTooltip();   // hover reveals the untruncated state (never a masked one)
```

The native `tooltip()`, `copyable()`, `copyMessage()`, and `copyMessageDuration()` keep
working; an explicit `tooltip()` wins over `fullStateTooltip()`.

#### Advanced badges

Beyond the native `badge()` (which turns the _state itself_ into badges), an unlimited
number of fully independent badges can be attached next to the content:

```php
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\AdvancedBadge;

AdvancedTextColumn::make('status')
    ->badges([
        AdvancedBadge::make('Verified')
            ->color('success')
            ->border()
            ->pulse(),

        AdvancedBadge::make('Premium')
            ->color('warning')
            ->bounce(),

        '2FA Enabled', // plain strings become label-only badges
    ]);
```

Every option accepts a static value or a closure with the usual `$record` / `$state`
injections — one badge instance is shared across all rows and resolved lazily per cell:

```php
AdvancedBadge::make(fn (User $record): string => $record->status)
    ->color(fn (User $record): string => match ($record->status) {
        'active' => 'success',
        'pending' => 'warning',
        'banned' => 'danger',
    })
    ->visible(fn (User $record): bool => $record->is_active)
    ->tooltip(fn (User $record): string => "Updated {$record->updated_at}");
```

The full builder API:

- **Label** — `make()` / `label()` (string, `Htmlable`, or closure), `translateLabel()`.
  Empty labels skip the badge entirely.
- **Icon** — `icon($icon, $position)`, `iconPosition()`, `iconColor()` (semantic Filament
  name or any CSS color).
- **Colors** — `color()` takes a semantic name or `Color` palette array, resolved exactly
  like a native badge (dark mode included); `backgroundColor()` and `textColor()` override
  just one channel.
- **Shape** — `border()`, `borderColor()`, `borderWidth()`, `borderRadius()`, `rounded()`,
  `pill()`, `outline()`, `filled()`, `size('xs'|'sm'|'md'|'lg')`.
- **Animation** — `pulse()`, `bounce()` (respecting `prefers-reduced-motion`), or any
  animation registered with `BadgeAnimations::register('wiggle', 'my-wiggle-class')` via
  `animation('wiggle')`. All accept a condition.
- **Visibility** — `visible()`, `hidden()`, and `authorize()` (a gate ability checked
  against the record, or a closure).
- **Responsive visibility** — `visibleFrom('md')` / `hiddenFrom('lg')` show or hide the badge
  per screen size, using the same breakpoints (`sm`, `md`, `lg`, `xl`, `2xl`) as Filament's
  column `visibleFrom()` / `hiddenFrom()`.
- **Interaction** — `url($url, shouldOpenInNewTab: true)` renders a real anchor;
  `wireClick('method')` and `alpineClick('expression')` add Livewire / Alpine handlers with
  keyboard-accessible button semantics (`role="button"`, `tabindex`, Enter/Space);
  `extraAttributes([...])` is the escape hatch for anything else; `classes([...])` adds CSS
  classes.
- **Tooltips** — `tooltip()` uses Filament's tippy integration.

> [!NOTE]
> When the column itself is a link — via the column's `url()` / `action()`, or the table's
> `recordUrl` / `recordAction` — Filament wraps the whole cell in an `<a>` / `<button>`.
> Because an anchor cannot be nested inside another anchor (the browser would tear the markup
> apart and drop the badge), an interactive badge in that context automatically degrades to a
> keyboard-accessible `role="link"` element that navigates via script and stops the click from
> also triggering the surrounding cell link. Non-interactive badges are unaffected, and the
> behavior is transparent — you configure `url()` / `wireClick()` the same way regardless.

Extensibility mirrors the rest of the package: subclass `AdvancedBadge`, add macros
(`AdvancedBadge::macro()`), register reusable presets —

```php
AdvancedBadge::registerPreset('pii', fn (AdvancedBadge $badge) => $badge
    ->color('danger')
    ->outline()
    ->tooltip('Contains personal data'));

AdvancedBadge::make('Sensitive')->preset('pii');
```

— or rebind the `RendersBadges` contract to swap the renderer globally. Rendering is
plain server-side string building on top of an immutable per-cell `BadgeViewModel`, so
tables with thousands of rows stay fast: no per-badge Blade views, no repeated object
construction, one DOM element per badge.

#### Native state badges & everything else

Badges from the state itself, colors, and separators are already first-class in
`TextColumn`, so nothing is duplicated — it all just works through `AdvancedTextColumn`:

```php
AdvancedTextColumn::make('tags')
    ->separator(',')
    ->badge()
    ->color(fn (string $state): string => $state === 'urgent' ? 'danger' : 'gray');
```

The same goes for `description()`, `placeholder()`, `wrap()`, `lineClamp()`,
`listWithLineBreaks()`, `limitList()`, `html()` / `markdown()` (sanitized), visibility
(`visible()` / `hidden()`), and every other native feature. Output is escaped by default;
HTML rendering is opt-in and sanitized by Filament.

#### Extending

The column is built for extension:

```php
// Macros — add your own fluent methods:
AdvancedTextColumn::macro('pii', function () {
    /** @var AdvancedTextColumn $this */
    return $this->masked(fn (): bool => auth()->user()->cannot('viewPii'))->copyable();
});

// Global defaults for every instance:
AdvancedTextColumn::configureUsing(fn (AdvancedTextColumn $column) => $column->fullStateTooltip());

// Rendering decorators — transform the final cell HTML (runs last, in order):
AdvancedTextColumn::make('name')
    ->decorateHtmlUsing(fn (string $html): string => "<div class=\"sparkle\">{$html}</div>");
```

Services are bound to contracts, so masking and link generation can be swapped globally
in a service provider without touching any column:

```php
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;

$this->app->singleton(MasksText::class, MyRegexMasker::class);
$this->app->singleton(GeneratesLinks::class, MyTrackingLinkGenerator::class);
```

Subclasses can override `wrapEmbeddedHtml()` (the wrapper markup), `getImageRenderer()`
(the `<img>` markup), `formatState()`, or `getUrl()` — each override point is small and
documented in the source.

#### Upgrading & compatibility notes

- `AdvancedTextColumn` extends `TextColumn` and never re-implements its rendering: the
  parent renders the cell, and a wrapper is only added when masking, affixes, typography,
  or the character count are configured. Unconfigured columns render byte-for-byte native
  output, so swapping `TextColumn::make(...)` for `AdvancedTextColumn::make(...)` is safe.
- Searching and sorting always operate on the **database value**; masking is purely a
  presentation concern. If a value must never leave the server unmasked, keep using
  policies/attribute casts — a table search on a masked column can still confirm a value
  exists.

#### Performance

Rendering is pure server-side string building on top of the parent's output — no Blade
sub-views, no per-cell view resolution. Feature checks short-circuit, state is read through
Filament's per-record cache, and services resolve once from the container as singletons,
so the column is comfortable on tables with thousands of rows.

### PackageComparison

A Fiverr-style package comparison editor as a real Filament form field. Columns are
**packages** (add, remove, rename, drag to reorder), rows are **features** with a type,
and every cell is the value of one feature for one package. The whole table is a single
JSON state on your model — no extra tables, no repeaters.

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PackageComparison;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\BooleanRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\DeliveryRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\DescriptionRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\NumberRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\PriceRow;

PackageComparison::make('packages')
    ->minPackages(1)
    ->maxPackages(6)
    ->defaultPackages(3)
    ->allowPackageReordering()
    ->allowFeatureReordering()
    ->allowedRowTypes([
        DescriptionRow::class,
        BooleanRow::class,
        NumberRow::class,
        PriceRow::class,
        DeliveryRow::class,
    ])
    ->defaultRows([
        ['label' => 'Description', 'type' => 'description'],
        ['label' => 'Price', 'type' => 'price', 'config' => ['currency' => 'EUR']],
        ['label' => 'Delivery', 'type' => 'delivery'],
    ])
    ->collapsible()
```

Cast the attribute to `array` (or use a JSON column) and the field stores:

```json
{
    "packages": [{ "id": "9c2e…", "title": "Starter", "meta": {} }],
    "rows": [
        {
            "id": "d7f3…",
            "label": "Responsive Design",
            "type": "boolean",
            "config": {},
            "values": { "9c2e…": true }
        }
    ]
}
```

UUIDs identify packages and rows, so reordering is pure array order and cell values
survive renames. Every configuration option accepts a `Closure`, exactly like native
Filament fields.

#### How it behaves

- **All interactivity is client-side.** The table is stamped by Alpine from the
  Livewire-entangled state; adding, removing, renaming, reordering (drag & drop via
  Filament's own sortable plugin) and cell edits fire **zero requests** until the form
  submits — or sync live if you chain `->live()`.
- **The server never trusts the browser.** On hydration _and_ dehydration the payload is
  re-normalized: unknown row types are dropped, orphaned cell values pruned, per-type
  config whitelisted, every cell value coerced through its row type, and
  `minPackages()` / `maxPackages()` are enforced as validation rules.
- **UX**: sticky feature column, horizontal scrolling as packages grow, inline renames,
  a per-row settings popover (options for select/radio rows, currency for price rows),
  collapsible editor, full dark-mode styling, keyboard-accessible controls.

#### Built-in row types

| Type                                | Cell editor                                     | Per-row settings |
| ----------------------------------- | ----------------------------------------------- | ---------------- |
| `boolean`                           | Checkmark                                       | —                |
| `text` / `textarea` / `description` | Text input / textarea                           | —                |
| `number`                            | Number input                                    | —                |
| `price`                             | Number input with currency prefix               | Currency         |
| `select` / `radio`                  | Dropdown / radio group                          | Options list     |
| `delivery`                          | Amount + unit (`{"amount": 7, "unit": "days"}`) | —                |
| `footer`                            | Per-package call-to-action line                 | —                |

#### Custom row types

Row types follow a Strategy pattern: subclass
`Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\RowType`,
point `getCellView()` at a Blade view containing a
`<template x-if="row.type === 'rating'">` block, and register the class — the type
picker, cell rendering, client-side seeding, and server-side coercion all follow
automatically, without touching any package code:

```php
class RatingRow extends RowType
{
    public function getName(): string
    {
        return 'rating';
    }

    public function getDefaultValue(): mixed
    {
        return 0;
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        return is_numeric($value) ? max(0, min(5, (int) $value)) : 0;
    }

    public function getCellView(): string
    {
        return 'forms.cells.rating'; // your own view, any namespace
    }
}
```

Inside the cell view you get the Alpine scope `row`, `pkg`, and the entangled state —
bind with `x-model="row.values[pkg.id]"` and you're done.

### AdvancedSelect

A drop-in superset of Filament's `Select` that renders **rich options** — a leading icon, a
secondary description line, a Filament color, and a trailing badge — for each option, with lazy
evaluation on every value.

```
┌─────────────────────────────────────────────┐
│  🌐  Published                         Live  │
│      Visible to everyone                     │
├─────────────────────────────────────────────┤
│  ✏️  Draft                                    │
│      Only visible to you                     │
└─────────────────────────────────────────────┘
```

**What it looks like:**

- _Dropdown:_ each row shows an icon on the left, the label with an optional smaller,
  muted description underneath, and an optional badge pushed to the right edge. The label
  can be tinted with any Filament color.
- _Selected value:_ a compact version — icon, label, and badge, without the description — so
  the control never grows taller than a native one. Multi-select chips use the same compact form.
- _Everything else is native:_ search, `multiple()`, relationships, validation, create/edit
  option actions, and Livewire reactivity all behave exactly as they do on `Select`, because
  `AdvancedSelect` **extends** it and only layers rendering on top.

Because a plain `AdvancedSelect` (with a `value => label` array) is byte-for-byte a `Select`,
you can rename `Select` to `AdvancedSelect` in an existing form with zero behavior change and
opt into richness one option at a time.

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOption;

AdvancedSelect::make('status')
    ->placeholder('Select a status')
    ->searchable()
    ->options([
        SelectOption::make('draft', 'Draft')
            ->icon('heroicon-o-pencil-square')
            ->description('Only visible to you')
            ->color('gray'),
        SelectOption::make('published', 'Published')
            ->icon('heroicon-o-globe-alt')
            ->description('Visible to everyone')
            ->color('success')
            ->badge('Live'),
        SelectOption::make('archived', 'Archived')
            ->icon('heroicon-o-archive-box')
            ->color('warning')
            ->disabled(fn (?Post $record): bool => $record?->is_locked ?? false),
    ]);
```

#### The option builder

Every `SelectOption` setter accepts a static value **or** a closure with the field's usual
`$state`, `$get`, `$record`, `$livewire`, and `$component` injections (plus `$option`, the
option itself), so nothing is evaluated until render time.

| Method                                          | Purpose                                                                        |
| ----------------------------------------------- | ------------------------------------------------------------------------------ |
| `make($value, $label = null)`                   | The value stored in state and its label (defaults to the value).               |
| `label()` / `translateLabel()`                  | Set / translate the label.                                                     |
| `description()`                                 | A muted secondary line under the label.                                        |
| `icon()` / `iconColor()`                        | A leading icon and its color.                                                  |
| `color()`                                       | Tints the label; inherited by the badge.                                       |
| `badge($label, $color = null)` / `badgeColor()` | A trailing badge.                                                              |
| `badgeAlign('start' \| 'end')`                  | Badge position on the label line — next to the label (default) or the far end. |
| `disabled()`                                    | Render the option but block selection.                                         |
| `visible()` / `hidden()`                        | Conditionally include the option.                                              |
| `group()`                                       | Place the option under an optgroup heading.                                    |
| `classes()` / `extraAttributes()`               | Extra CSS classes / HTML attributes on the option element.                     |

Colors accept a semantic Filament name (`success`, `danger`, …), a `Color` palette array (e.g.
`Color::Blue` or `Color::hex('#8b5cf6')` from `Filament\Support\Colors\Color`), or any literal CSS
color:

```php
use Filament\Support\Colors\Color;

SelectOption::make('pro', 'Pro')
    ->color(Color::Blue)
    ->badge('New', Color::Amber);
```

#### Layering richness onto a plain list

If you already have a `value => label` array (from an enum, a config, a query), keep it and add
the extras through parallel maps — no need to rewrite it into objects:

```php
AdvancedSelect::make('status')
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->icons(['draft' => 'heroicon-o-pencil-square', 'published' => 'heroicon-o-globe-alt'])
    ->descriptions(['draft' => 'Only visible to you'])
    ->optionColors(['published' => 'success'])
    ->optionBadges(['published' => 'Live']);
```

#### Enums

Pass an enum class and `AdvancedSelect` reads [Filament's enum
contracts](https://filamentphp.com/docs/5.x/advanced/enums) straight off each case — no maps
required:

```php
use Filament\Support\Contracts\{HasColor, HasDescription, HasIcon, HasLabel};
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\HasBadge;

enum Priority: string implements HasLabel, HasIcon, HasColor, HasDescription, HasBadge
{
    case Low = 'low';
    case High = 'high';

    public function getLabel(): string { /* … */ }
    public function getIcon(): string { /* … */ }
    public function getColor(): string { /* … */ }
    public function getDescription(): string { /* … */ }
    public function getBadge(): ?string { /* … */ }
}

AdvancedSelect::make('priority')->options(Priority::class);
```

- `HasLabel` → the option label
- `HasIcon` → the leading icon
- `HasColor` → the label tint
- `HasDescription` → the description line
- `HasBadge` → a trailing badge (inherits the case's `HasColor` color)

The first four are Filament's own [enum contracts](https://filamentphp.com/docs/5.x/advanced/enums);
`HasBadge` is the package's own contract (Filament ships none for badges) and lives at
`Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\HasBadge`.

Rich rendering activates **automatically** whenever a case carries an icon, color, description, or
badge; a label-only enum (or a plain one) stays fully native, since the native `Select`
already renders those. Either way the enum is still registered for state casting, so selected
values hydrate and dehydrate as enum instances exactly as on a native `Select`.

You can still layer the parallel maps on top to override or fill in per-value — the enum
provides the defaults, the map wins where it has an entry:

```php
AdvancedSelect::make('priority')
    ->options(Priority::class)
    ->descriptions(['high' => 'Escalated — respond within the hour']);
```

The maps only touch options generated from a plain pair or an enum case; explicitly authored
`SelectOption` objects always win. Anything you pass as a **closure** (a dynamic list,
`relationship()`) stays fully native — express lazy rich options with per-property closures on a
static list instead.

#### Grouping options

Options render inside native optgroups, the
[same way Filament groups a `Select`](https://filamentphp.com/docs/5.x/forms/select#grouping-options).
Give a `SelectOption` a `group()`:

```php
AdvancedSelect::make('assignee')->options([
    SelectOption::make('me', 'Me')->group('You'),
    SelectOption::make('alex', 'Alex')->group('Team'),
    SelectOption::make('sam', 'Sam')->group('Team'),
]);
```

…or use Filament's nested `groupLabel => [value => label]` array — which also accepts the parallel
maps, so you can enrich a grouped list you already have:

```php
AdvancedSelect::make('status')
    ->options([
        'Published'   => ['live' => 'Live', 'scheduled' => 'Scheduled'],
        'Unpublished' => ['draft' => 'Draft'],
    ])
    ->icons(['live' => 'heroicon-o-globe-alt']);
```

Grouping applies to search results too: matches stay under their group headings.

#### Layout

Each dropdown row is laid out as an icon beside a body; the body's first line carries the label
and badge together, and the description — when present — drops onto its own line beneath them:

```
[icon]  Label  [Badge]
        Description line
```

By default the badge hugs the label; push it to the far end of the row with `badgeAlign('end')`
(or the `BadgeAlignment` enum):

```php
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Enums\BadgeAlignment;

SelectOption::make('pro', 'Pro')->badge('Popular')->badgeAlign('end');
SelectOption::make('pro', 'Pro')->badge('Popular')->badgeAlign(BadgeAlignment::End);
```

```
badgeAlign('start')          badgeAlign('end')
[icon]  Label [Badge]        [icon]  Label        [Badge]
```

A description can appear or disappear without shifting the icon, label, or badge. The selected
value (and each multi-select chip) uses the compact single line — icon, label, badge — and never
shows the description. Restyle any of it through `advanced-select.css`, a decorator, or a custom
renderer (below).

#### Presets

Name a reusable bundle of configuration once and apply it anywhere — at the option level or the
component level:

```php
SelectOption::registerPreset('archived', fn (SelectOption $o) => $o
    ->icon('heroicon-o-archive-box')->color('gray')->badge('Archived')->disabled());

AdvancedSelect::registerPreset('post-status', fn (AdvancedSelect $s) => $s
    ->searchable()
    ->options(PostStatus::options()));

AdvancedSelect::make('status')->preset('post-status');
```

#### Extending

The rendering pipeline is `raw config → evaluate → resolve (view models) → decorate → render`,
and every stage is replaceable without touching the component.

- **Decorators** — wrap the rendered HTML for a one-off tweak:

    ```php
    AdvancedSelect::make('status')
        ->options([...])
        ->decorateOptionUsing(fn (string $html): string => "<div class=\"px-1\">{$html}</div>")
        ->decorateSelectedLabelUsing(fn (string $html): string => "<span class=\"font-medium\">{$html}</span>");
    ```

- **Custom renderer** — swap the markup wholesale by implementing `RendersOptions`. Bind it
  globally in a service provider…

    ```php
    use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;

    $this->app->bind(RendersOptions::class, MyOptionRenderer::class);
    ```

    …or per-field with `->renderOptionsUsing(new MyOptionRenderer())`. Subclass the default
    `OptionRenderer` to override just `renderOption()` or `renderSelectedLabel()`.

- **Macros** — both `SelectOption` and `AdvancedSelect` are `Macroable`.

Each resolved option becomes an immutable `OptionViewModel`; the renderer only ever assembles
markup from that value object, so evaluation and rendering stay independent and testable.

#### Performance

Options are evaluated once per component and the resulting view models are memoized, then reused
across the dropdown, the selected-label lookup, the disabled check, and search within the same
request — so a form with many `AdvancedSelect`s does no duplicate work. Call `flushOptionCache()`
if you mutate configuration after a first render. Rich mode stays entirely dormant (no HTML, no
JS select, no allocation) until you actually use a rich feature.

#### Accessibility & security

The rich markup renders through Filament's `allowHtml()` path, which `AdvancedSelect` enables for
you only when rich options are active. Labels, descriptions, and badge text are always escaped;
only trusted, developer-authored icon SVGs are emitted raw — never pass unsanitised user input as
option HTML.

### Separator

A purely visual layout component for a Schema (form or infolist) — the divider equivalent of
`Section`, `Grid`, or `Fieldset`. It draws a line, optionally split by a centered label and/or
icon, and nothing else: no field name, no state, no validation, no dehydrated value.

```
──────────────  General Information  ──────────────

────────────────────────  OR  ───────────────────────

┊
┊  (vertical, inside a Flex)
┊
```

**What it looks like:**

- _Plain divider:_ `Separator::make()` — a single full-width line, exactly like an HTML `<hr>`.
- _Labeled:_ `Separator::make('General Information')` — the line splits around a small, muted,
  centered label.
- _Decorative:_ `Separator::make()->text('OR')` — `text()` is an alias for `label()`, reading
  naturally for a divider that isn't introducing a new section.
- _Dark mode:_ the line color is read from the panel's gray scale, so custom themes restyle it
  automatically.

Because it extends the same base `Filament\Schemas\Components\Component` that `Section`, `Grid`,
and `Fieldset` do — rather than `Entry` or `Field` — a `Separator` never reads or writes state,
never needs a name, and never appears in a form's submitted payload.

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;

Separator::make();

Separator::make('General Information');

Separator::make()
    ->label('Billing')
    ->icon('heroicon-o-credit-card');

Separator::make()
    ->text('OR')
    ->dashed()
    ->alignCenter();
```

#### Label & icon

`label()` accepts a string, an `Htmlable`, or a closure evaluated lazily like everything else in
Filament. `hiddenLabel()` keeps the separator visible while removing the label:

```php
Separator::make('Billing')->hiddenLabel(fn (): bool => ! auth()->user()->isAdmin());
```

`icon()` / `iconPosition('before' | 'after')` place a small icon next to the label, using
Filament's own `HasIcon` / `HasIconPosition` concerns — any Blade icon name or `Heroicon` case
works.

#### Orientation

```php
Separator::make()->horizontal(); // default: a full-width line
Separator::make()->vertical();   // a full-height line, for use inside a Flex/Grid
```

#### Variants

`default()`, `subtle()`, `muted()` control the line's tone; `solid()`, `dashed()`, `dotted()`
control its pattern; `zigzag()` draws a repeating sawtooth line via a conic-gradient mask:

```php
Separator::make()->dashed();
Separator::make()->subtle();
Separator::make()->zigzag();
```

This isn't a closed set — `variant('brand')` accepts any string, and the renderer attaches a
matching `fi-separator-variant-brand` class for you to style in your own theme, so a custom
variant needs no package changes.

#### Label typography

The optional label supports the same typography API as Filament's schema `Text` component —
`size()`, `weight()`, and `fontFamily()` — using Filament's `TextSize`, `FontWeight`, and
`FontFamily` enums (or raw class-name strings):

```php
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

Separator::make('Section')
    ->size(TextSize::Large)
    ->weight(FontWeight::Bold)
    ->fontFamily(FontFamily::Mono);
```

#### Thickness

`thick()`, `thin()`, and `thickness()` control line weight on **every** variant. Border-based
lines (solid, dashed, dotted, …) map to `--fi-separator-thickness`; the zigzag variant maps
the same presets to its tooth geometry instead (see below).

```php
Separator::make()->dashed()->thick();      // 3px border
Separator::make()->solid()->thick(5);      // 5px border
Separator::make()->dotted()->thin();       // 1px border
Separator::make()->thickness('2px');       // explicit CSS length
```

Integers are treated as pixels; strings such as `'0.125rem'` pass through unchanged.

#### Zigzag sizing

`zigzag()` uses a conic-gradient mask. Tune the tooth pattern with `zigzagSize()`,
`zigzagDepth()`, and `zigzagAngle()`, or use the shared `thick()` / `thin()` presets:

```php
// Presets (zigzag only — border variants ignore zigzag variables)
Separator::make()->zigzag()->thick();          // 100px period, 35px depth
Separator::make()->zigzag()->thick(60, 20);    // custom period and depth
Separator::make()->zigzag()->thin();           // 4px period, 1px depth

// Granular control
Separator::make()->zigzag()
    ->zigzagSize('2rem')
    ->zigzagDepth('0.5rem')
    ->zigzagAngle(90);                         // 90 or '90deg'
```

These resolve to CSS custom properties on the separator root:

| Call              | Border variants                 | Zigzag variant                                  |
| ----------------- | ------------------------------- | ----------------------------------------------- |
| `thick()`         | `--fi-separator-thickness: 3px` | `--fi-separator-zigzag-s: 100px`, `-b: 35px`    |
| `thick(5)`        | `--fi-separator-thickness: 5px` | same as `thick()` unless a second arg is passed |
| `thick(60, 20)`   | ignored                         | `--fi-separator-zigzag-s: 60px`, `-b: 20px`     |
| `thin()`          | `--fi-separator-thickness: 1px` | `--fi-separator-zigzag-s: 4px`, `-b: 1px`       |
| `zigzagAngle(90)` | —                               | `--fi-separator-zigzag-a: 90deg`                |

Without `thick()` / `thin()` / the granular setters, zigzag falls back to the package stylesheet
defaults. Override any variable in your own CSS or via `extraAttributes(['style' => '...'])` if
you prefer.

#### Color

`color()` tints the line, label, and icon. It accepts the same three shapes as Filament's own
`HasColor` concern — a registered color name, a raw `Color` palette array, or a literal CSS
color — and every value may be a closure:

```php
use Filament\Support\Colors\Color;

Separator::make('Danger Zone')->color('danger'); // a registered semantic name
Separator::make()->color('info');                // any registered color
Separator::make()->color(Color::Blue);           // a raw Color palette array
Separator::make()->color('#22d3ee');             // a literal CSS color
Separator::make()->color(fn (): string => 'success'); // resolved lazily
```

The line is tinted (mixed with transparency so it stays a divider, not a heavy rule) while the
label and icon take the full color. With no `color()` call the separator keeps its neutral gray,
and both themes are handled by a single `color-mix()` — no dark-mode value to pass.

#### Spacing, width & alignment

```php
Separator::make()
    ->margin('lg')        // space before AND after (Size preset, raw CSS length, or px int)
    ->padding(12)          // gap between the line and the label/icon
    ->spaceBefore('2rem')  // overrides margin() for just the space before
    ->spaceAfter(0);       // overrides margin() for just the space after

Separator::make()->width(240);      // a short, 240px line instead of the full width
Separator::make()->width(240)->fullWidth(); // back to spanning the full width

Separator::make('OR')->alignStart(); // pushes the label toward the start instead of the center
```

`maxWidth()`, `columnSpan()`, `hidden()`/`visible()`, and `extraAttributes()` all come from the
base schema `Component` for free — no extra API to learn.

#### Extensibility

- **Custom variants** — `variant('brand')` plus a `.fi-separator-variant-brand` rule in your own
  CSS; no subclassing needed.
- **Presets** — register a reusable configuration once:

    ```php
    Separator::configureUsing(fn (Separator $separator) => $separator->subtle()->margin('lg'));
    ```

- **A different renderer** — rebind `RendersSeparator` to change how every separator in the app
  renders, down to skipping Blade entirely:

    ```php
    use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;

    $this->app->bind(RendersSeparator::class, MySeparatorRenderer::class);
    ```

- **Macros** — `Separator` is `Macroable`, like every Filament component.

### OtpInput

A one-time-code / PIN field rendered as a row of single-character cells, backed by a single
scalar string. It behaves like a first-party Filament field — `live()`, `afterStateUpdated()`,
`formatStateUsing()`, validation, hydration, and dehydration all work exactly as on a
`TextInput` — while all of the typing experience (auto-advance, backspace-to-previous, arrow /
Home / End / Delete navigation, selection replacement, paste distribution, invalid-character
rejection, masking, and auto-submit) happens client-side in an entangled Alpine component, so no
Livewire round-trip is needed to move between cells.

```text
┌─┐┌─┐┌─┐   ┌─┐┌─┐┌─┐
│1││2││3│ - │4││5││6│
└─┘└─┘└─┘   └─┘└─┘└─┘
```

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;

OtpInput::make('code')
    ->length(6);

OtpInput::make('verification_code')
    ->length(6)
    ->numeric()
    ->autocomplete()   // browser one-time-code (SMS / authenticator) autofill
    ->autoSubmit()     // fire an `otp-completed` event once every cell is filled
    ->group(3)         // render as 123 - 456
    ->separator('-');
```

Despite the multiple cells, the field's value is one plain string — the separator-free code
(`"123456"`) — sanitized to the configured mode and length on the way in and out, so whatever the
browser sends, the stored value is always clean.

**Length & mode**

```php
OtpInput::make('code')->length(4);
OtpInput::make('code')->length(fn (): int => 6);   // lazily evaluated

OtpInput::make('code')->numeric();        // 0-9 (the default), numeric mobile keyboard
OtpInput::make('code')->alphabetic();     // A-Z, a-z
OtpInput::make('code')->alphanumeric();   // A-Z, a-z, 0-9
```

The chosen mode drives client-side keystroke filtering, the mobile `inputmode`, **and** the
server-side validation rule — from one source of truth — so an exact-length, correct-alphabet
code is enforced on both ends automatically (empty optional values are left to `required()`).

**Grouping & separators**

```php
OtpInput::make('code')->length(6)->group(3);                 // 123 - 456
OtpInput::make('code')->length(7)->group([2, 3, 2]);          // uneven groups
OtpInput::make('code')->group(3)->separator('•');             // any glyph
OtpInput::make('code')->group(3)->separator(fn () => '-');    // or a closure
```

Grouping and separators imply each other: `->group(3)` on its own renders the default `-`
separator, and `->separator('•')` on its own enables the default grouping — so you only call the
one you care about. An explicit configuration is never overwritten, and `->group(3)->separator(null)`
keeps the grouping while dropping the separator.

Grouping and separators are purely visual — never part of the stored value, and marked
`aria-hidden` so screen readers announce a clean code.

The cursor also always lands where typing should begin: clicking (or tabbing to) an empty cell
ahead of the first unfilled one bounces focus back to that first unfilled cell, so a code can't be
started from the middle; clicking a cell that already holds a character keeps focus there so it
can be edited in place.

**Private, autocomplete & auto-submit**

```php
OtpInput::make('recovery_code')->private();                  // masks each cell like a password
OtpInput::make('recovery_code')->private()->maskCharacter('*');

OtpInput::make('code')->autocomplete();                      // autocomplete="one-time-code" on cell 1

OtpInput::make('code')->autoSubmit();                        // dispatch `otp-completed` when full
OtpInput::make('code')->autoSubmit('verify');               // …and call the `verify` Livewire method
```

`autoSubmit()` dispatches a cancelable `otp-completed` browser event carrying the value (listen
with `x-on:otp-completed`, or on the Livewire root, to submit the surrounding form); passing a
method name additionally calls that Livewire method directly.

**Appearance**

```php
OtpInput::make('code')->large();      // or ->compact(), ->small()
OtpInput::make('code')->square();     // or ->rounded() (default)
OtpInput::make('code')->cellWidth(48)->cellGap('0.75rem');
OtpInput::make('code')->alignment('center'); // or ->alignStart(), ->alignEnd()
```

Everything from the base field — `label()`, `hiddenLabel()`, `helperText()`, `hint()`,
`placeholder()`, `required()`, `disabled()`, `readOnly()`, `autofocus()`, `rules()`,
`extraAttributes()` — works as usual. All configuration accepts closures.

**Accessibility.** The row is a `role="group"` labeled by the field label; each cell carries a
`Character N of M` label, `aria-invalid` flips on when validation fails, focus is managed on every
keystroke, and a `forced-colors` block keeps it usable in high-contrast mode.

**Extensibility.** `OtpInput` is `Macroable` and supports `configureUsing()` for presets; the
per-cell character policy, `inputmode`, and validation regex all derive from the `OtpMode` enum,
and the Alpine layer is split into small, replaceable managers (state, focus, keyboard,
clipboard).

### AdvancedToggle

A drop-in superset of Filament's `Toggle` whose core feature is a confirmation modal gating
every state change — built entirely on Filament's own `Action` and action-modal
infrastructure, not a bespoke dialog.

```
false ──click──▶ [ modal: "Enable Feature?" ] ──confirm──▶ true
  ▲                                    │
  └──────────────cancel────────────────┘
```

**The guarantee:** without `requiresConfirmation()`, an `AdvancedToggle` renders and behaves
byte-for-byte like a native `Toggle`. The moment it's used, the switch **cannot** flip, revert,
or flicker on its own — the click is intercepted client-side _before_ it ever reaches the
entangled Livewire property, and the value only ever changes as the result of a real,
server-side confirmed action call. There is no "set then undo": the previous state is simply
never touched unless confirmation succeeds.

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;

AdvancedToggle::make('enabled')
    ->requiresConfirmation(
        title: 'Enable feature',
        description: 'Are you sure you want to enable this for everyone?',
    );
```

Clicking the toggle opens a confirmation modal; the switch stays put until the user confirms.
Cancelling — or dismissing the modal any other way — leaves the value untouched.

#### Confirmation appearance

Every option accepts a static value **or** a closure, and can be set either inline via
`requiresConfirmation()`'s named arguments, or through a dedicated fluent setter:

```php
AdvancedToggle::make('enabled')
    ->requiresConfirmation(
        title: 'Enable feature',        // alias: heading (heading wins if both are set)
        description: 'Are you sure?',
        icon: 'heroicon-o-bolt',
        iconColor: 'warning',
        width: 'sm',
        alignment: 'center',
        confirmButtonLabel: 'Yes, enable it',
        cancelButtonLabel: 'Never mind',
        confirmButtonColor: 'success',
        cancelButtonColor: 'gray',
    );

// Equivalent, one setter at a time:
AdvancedToggle::make('enabled')
    ->requiresConfirmation()
    ->confirmationTitle('Enable feature')
    ->confirmationDescription(fn ($record) => "Enable this for {$record->name}?")
    ->confirmationIcon('heroicon-o-bolt')
    ->confirmationIconColor('warning')
    ->confirmationWidth('sm')
    ->confirmationAlignment('center')
    ->confirmationConfirmButtonLabel('Yes, enable it')
    ->confirmationCancelButtonLabel('Never mind')
    ->confirmationConfirmButtonColor('success')
    ->confirmationCancelButtonColor('gray');
```

#### Custom modal content & validation

`confirmationSchema()` accepts any Filament schema components — the confirmation modal is a
real Filament action form, so validation, hydration, and every native field behave exactly as
they would anywhere else:

```php
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

AdvancedToggle::make('enabled')
    ->requiresConfirmation()
    ->confirmationSchema([
        TextInput::make('password')->password()->required(),
        Textarea::make('reason'),
    ]);
```

If validation fails, Filament's own action-modal validation keeps the modal open and reports
the errors inline — the toggle's state is never touched, because the confirmation callback
never runs.

#### Callbacks

```php
AdvancedToggle::make('enabled')
    ->requiresConfirmation()
    ->confirmationSchema([TextInput::make('reason')])
    ->onConfirm(function (bool $newState, bool $oldState, array $data) {
        // Runs before the state commits. Throw to reject the change (see below).
        AuditLog::create(['reason' => $data['reason']]);
    })
    ->onCancel(function (bool $oldState) {
        Log::info('Toggle change cancelled');
    })
    ->afterConfirmed(function (bool $newState, bool $oldState) {
        // Runs after the new state has been committed — ideal for audit logging.
    });
```

`onCancel()` only costs a request when it's actually registered: with no `onCancel()`
callback, cancelling stays Filament's native, instant client-side dismissal.

#### Conditional confirmation

```php
AdvancedToggle::make('enabled')->requiresConfirmation(); // always

AdvancedToggle::make('enabled')
    ->requiresConfirmation(fn ($record) => $record->isProtected()); // per record

AdvancedToggle::make('enabled')->requiresConfirmation(onlyWhenTurningOn: true);
AdvancedToggle::make('enabled')->requiresConfirmation(onlyWhenTurningOff: true);
```

The condition closure also receives `$newState` and `$oldState`, for logic that depends on the
direction _and_ something else (the current user, a related model, ...).

#### Asynchronous confirmation & failure handling

`onConfirm()` can safely perform database work — it runs inside the same database transaction
Filament wraps every action call in. Throwing rejects the change:

```php
AdvancedToggle::make('enabled')
    ->requiresConfirmation()
    ->onConfirm(function (bool $newState) {
        if (! app(FeatureGate::class)->canEnable()) {
            throw new RuntimeException('This feature is at capacity.');
        }

        app(FeatureGate::class)->enable();
    })
    ->confirmationFailureNotification(
        fn (Throwable $exception) => Notification::make()
            ->danger()
            ->title('Could not enable feature')
            ->body($exception->getMessage()),
    )
    ->keepConfirmationModalOpenOnFailure(); // default; ->closeConfirmationModalOnFailure() to close instead
```

Any database writes made before the exception are rolled back automatically. Pass `null` to
`confirmationFailureNotification()` to disable the notification entirely.

#### Dynamic labels, icons, colors & descriptions

`onIcon()`, `offIcon()`, `onColor()`, `offColor()`, and `inline()` are already native to
`Toggle` and work exactly as documented in
[Filament's own docs](https://filamentphp.com/docs/5.x/forms/toggle) — `AdvancedToggle` adds
labels, descriptions, tooltips, and a badge, one per state:

```php
AdvancedToggle::make('enabled')
    ->onColor('success')->offColor('danger')                       // native
    ->onIcon('heroicon-o-check')->offIcon('heroicon-o-x-mark')     // native
    ->onLabel('Enabled')->offLabel('Disabled')
    ->onDescription('Users can access this feature.')->offDescription('Feature is disabled.')
    ->onTooltip('Click to disable')->offTooltip('Click to enable')
    ->onBadge('Enabled', 'success')->offBadge('Disabled', 'gray');
```

The badge label/color both accept closures, so a badge isn't limited to describing the raw
boolean:

```php
->offBadge(fn ($record) => $record->archived_at ? 'Archived' : 'Disabled')
```

#### Read-only reasons

```php
AdvancedToggle::make('enabled')
    ->disabled(fn ($record) => $record->is_locked)
    ->disabledReason(fn ($record) => $record->is_locked
        ? 'Disabled because this record is locked.'
        : null);
```

The reason only ever renders while the field is genuinely disabled.

#### Animations

```php
->animated()              // on by default
->animationDuration(250);  // milliseconds
```

Controls the package's own additions (state description/badge transitions, the dimmed
"confirming" state) — the native switch already animates its own thumb and track.

#### Standalone (non-panel) usage

Inside a Filament panel, this all works with zero setup. Using `AdvancedToggle` on a plain
Livewire component (outside a panel), give it the same two things Filament's own
`Select::createOptionAction()` needs:

```php
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class EditSettings extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
}
```

```blade
{{-- somewhere in the Livewire view --}}
{{ $this->form }}

<x-filament-actions::modals />
```

#### Architecture

Responsibilities are split across small, single-purpose collaborators:

- `HasConfirmation`, `HasConfirmationForm` — the fluent configuration API;
- `ConfirmationManager` — turns that configuration into a real `Filament\Actions\Action`,
  reusing Filament's action-modal architecture entirely (heading, description, icon, width,
  alignment, footer buttons, schema validation, database transactions, notifications — nothing
  is reimplemented);
- `HasStateLabels`, `HasStateDescriptions`, `HasStateTooltips`, `HasStateBadge`,
  `HasDisabledReason`, `HasAnimations` — the smaller, independent presentation features;
- `ToggleViewModel` — the resolved, render-ready state the Blade view reads, so nothing is
  re-evaluated (or re-derived in JavaScript) more than once per render.

Swap how the confirmation action is built globally by rebinding
`Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction`,
or per-instance with `->buildConfirmationActionUsing(new MyConfirmationManager())` — the same
extension point this package uses for `RendersOptions` and `RendersSeparator`.

#### Accessibility

The switch is Filament's own native `role="switch"` button, untouched. The click-interception
wrapper adds `aria-busy` while a confirmation is being mounted and never intercepts a disabled
field (a native `disabled` button never dispatches a click event in the first place, so nothing
extra is needed there).

#### Performance

The state never mutates — and the Livewire component never re-renders — until confirmation
actually succeeds, so an unconfirmed click costs exactly one small request (mounting the
action) instead of a state update plus a corrective revert. Every confirmation option is
resolved once per render into a single `ToggleViewModel`; the two booleans that decide whether
a given click needs to be intercepted at all (`requiresConfirmationWhenTurningOn` /
`requiresConfirmationWhenTurningOff`) are computed server-side, so the client never re-derives
configuration — including whether to render the interception wrapper at all, which is skipped
entirely once neither direction currently requires confirmation.

### PhoneInput

An enterprise-grade international phone-number field, backed by
[libphonenumber](https://github.com/giggsey/libphonenumber-for-php) and behaving exactly like a
native Filament field. It handles local and international numbers, validation, formatting,
normalization, storage, and display — with a searchable country selector, flags, dial codes,
per-country example numbers, dynamic masking, extensions, and copy/clear affordances — without
you writing a line of phone logic.

```
┌──────────────────────────────────────────────┐
│ 🇬🇧 +44 │ 07400 123456          │  ⧉  ✕  │   selector · national input · copy · clear
└──────────────────────────────────────────────┘
   e.g. 07400 123456                                example hint (optional)
```

**One clean scalar, PHP as the source of truth.** However rich the widget looks, the field's
state is a single string. The client composes a *self-describing* E.164 transport value
(`+<code><digits>`, plus an optional `;ext=`) and the server does everything that matters with
it — parses it with libphonenumber, validates it, and normalizes it into your chosen storage
format. Nothing the client asserts is trusted; the number is parsed, validated, and formatted
again server-side. Because the transport is self-describing, the country is embedded in the
value: the selector is a convenience, not a second column to keep in sync.

**Everything is offline.** The country catalog, calling codes, example numbers, validity, and
line types all come from libphonenumber's bundled dataset. The field never makes a network
request to validate or format a number.

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PhoneInput;

PhoneInput::make('phone');
```

That's a complete, validating, international phone field: a searchable country selector, the
country detected from your app locale, E.164 storage, and international display. Everything
below is optional refinement.

```php
PhoneInput::make('phone')
    ->defaultCountry('GB')
    ->preferredCountries(['GB', 'US', 'DE'])
    ->showExample()
    ->copyable()
    ->clearable();
```

#### Storage & display formats

The field keeps the two formats it juggles deliberately separate — what lands in the database,
and how a saved value reads when it re-hydrates:

```php
PhoneInput::make('phone')
    ->storeE164()          // +14155552671  (default — country embedded, round-trips cleanly)
    ->displayInternational(); // +1 415-555-2671 (default)
```

| Storage                 | Example              | Display                    | Example              |
| ----------------------- | -------------------- | -------------------------- | -------------------- |
| `->storeE164()`         | `+14155552671`       | `->displayInternational()` | `+1 415-555-2671`    |
| `->storeNational()`     | `(415) 555-2671`     | `->displayNational()`      | `(415) 555-2671`     |
| `->storeInternational()`| `+1 415-555-2671`    | `->displayE164()`          | `+14155552671`       |
| `->storeRfc3966()`      | `tel:+1-415-555-2671`|                            |                      |
| `->storeRaw()`          | `14155552671`        |                            |                      |

Or pass a `PhoneFormat` (or a closure) directly to `->storeAs(...)` / `->displayAs(...)`. E.164
is the recommended storage format because the country is embedded; national and raw formats drop
the calling code, so they need a stable `->defaultCountry()` to hydrate back.

#### Validation

Every submitted number is re-parsed and re-validated server-side, whatever the client allowed.
Fields are **strict** by default — they require a genuinely valid, assigned number, not merely
one of plausible length:

```php
PhoneInput::make('phone')
    ->lenient()                              // accept any "possible" number (messy imports)
    ->mobileOnly()                           // or ->fixedLineOnly(), or:
    ->validateTypes([PhoneNumberType::Mobile, PhoneNumberType::Voip]);
```

Failures report a precise, translatable reason — `too_short`, `invalid_country`, `invalid_type`,
`country_not_allowed`, and so on — rather than a generic "invalid phone number". Blank optional
fields validate as absent; presence is the `required()` rule's job.

#### Country restrictions

```php
PhoneInput::make('phone')
    ->allowedCountries(['GB', 'US', 'DE'])   // or ->onlyCountries([...])
    ->blockedCountries(['RU'])               // allow-list wins when both are set
    ->preferredCountries(['GB', 'US']);      // pinned to the top, in order
```

All three accept ISO codes **or** a closure, so the catalog can depend on the record, the
tenant, or the authenticated user. A submitted number from a disallowed country fails validation
server-side.

#### Country detection

A fresh, empty field runs an ordered detection pipeline and starts on the first strategy that
yields an *allowed* country. The order is your priority:

```php
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\CountryDetectionStrategy;

PhoneInput::make('phone')
    ->detectCountry([
        CountryDetectionStrategy::UserPreference,
        CountryDetectionStrategy::AppLocale,
        CountryDetectionStrategy::Fallback,
    ])
    ->countryResolver(fn () => auth()->user()?->country)          // UserPreference
    ->detectCountryUsing(fn () => geoip(request()->ip())->iso_code) // Callback (GeoIP, headers, …)
    ->fallbackCountry('US');
```

`AppLocale` derives the region from Laravel's active locale (`en_GB` → `GB`). GeoIP, request
headers, and tenancy plug in through `detectCountryUsing()`, so the package never takes a
dependency on any of them. A number that already has a value dictates its own country — detection
only matters when there's nothing to derive from.

#### Capturing the number's own country

Detection (above) picks a *starting* country for an empty field. This is the opposite direction:
persisting the country a *filled-in* number actually belongs to — which may have nothing to do
with the user's profile country. A support agent logging a customer's alternate number, a signup
completed while travelling, a household's shared account: the number itself, not any ambient
setting, is the only trustworthy source for "which country is this phone from".

```php
PhoneInput::make('phone')
    ->captureCountryTo('phone_country')      // ISO alpha-2, e.g. "SA" — always 2 letters
    ->captureDialCodeTo('phone_dial_code');  // E.164 calling code, e.g. 966 — 1 to 3 digits, not unique
```

Both are off by default — call either (or both) to opt in. There's nothing to wire up: on save, the
resolved value is injected directly into the form's saved state next to the phone field's own key,
so a plain fillable column on the model is enough to receive it — no hidden field, no `$set()`, no
`->live()`. The path is relative to the field's own container, so it still lands correctly inside a
repeater or a nested `Group`. Each projects `null` while the number is blank or unparseable, and the
freshly resolved value the moment it becomes a real number — always derived from the number as
currently typed, never stale.

> [!TIP]
> Calling codes are 1 to 3 digits (`+1` for the US, `+44` for the UK, `+966` for Saudi Arabia) and
> several countries share one (`+1` also covers Canada and most of the Caribbean) — they're not a
> reliable fixed-width or unique value. Prefer `captureCountryTo()`'s ISO code as the stable
> identifier; reach for `captureDialCodeTo()` only when the numeric code itself is what you need
> (a `tel:` link, a billing rule keyed by calling code).

Need the value in a callback instead of persisting it — a validation rule, `afterStateUpdated()`,
`mutateFormDataBeforeSave()`? Both capture methods are just a convenience over accessors available
regardless of whether capture is enabled:

```php
function (PhoneInput $component) {
    $component->getCountry();   // ?string — the ISO code
    $component->getDialCode();  // ?int    — the calling code
}
```

#### Selector, extensions & affordances

```php
PhoneInput::make('phone')
    ->withoutCountrySelector()   // single-country field, national input only
    ->showFlags(false)
    ->showDialCode(false)
    ->enableSearch()             // search by name, ISO code, or dial code
    ->searchDebounce(200)
    ->enableExtension()          // +1 415-555-2671 ext 89 — preserved through the pipeline
    ->showExample()              // reactive "e.g. …" hint, follows the selected country
    ->showType()                 // Mobile / Landline / VOIP badge (server-resolved)
    ->copyable()                 // copy the normalized number
    ->clearable()
    ->placeholderFromCountry();  // default: the country's example number as the placeholder
```

Placeholders, masks, and example numbers all follow the selected country **reactively**, with no
Livewire round-trip. Pasting an international number (`+44 20 7946 0958`, or `0044…`) detects the
country, strips junk, and formats automatically.

#### Native Filament integration

`PhoneInput` extends `Field`, so every method you'd expect works unchanged:
`required()`, `disabled()`, `readOnly()`, `live()`, `reactive()`, `afterStateUpdated()`,
`dehydrated()`, `helperText()`, `hint()`, `extraAttributes()`, `columnSpan()`, and the rest.

#### Client events

The widget dispatches bubbling DOM events you can hook into with `x-on:` or a listener:

| Event                   | Detail                          | When                                   |
| ----------------------- | ------------------------------- | -------------------------------------- |
| `phone-changed`         | `{ value, country }`            | the number changed                     |
| `phone-country-changed` | `{ country, dialCode }`         | a country was selected                 |
| `phone-copied`          | `{ value }`                     | the copy button succeeded              |

#### Extending

Every layer is a contract resolved from the container, so you can swap one without touching the
field — per instance, or globally in a service provider:

```php
PhoneInput::make('phone')
    ->metadataProvider(new MyLeanCountryProvider)  // the whole country/parse/format source
    ->formatter(new MyHouseStyleFormatter)         // how numbers are formatted
    ->normalizer(new MyStorageNormalizer)          // exactly what gets persisted
    ->validator(new MyStrictValidator);            // the validation policy
```

```php
// Globally, in a service provider:
$this->app->bind(
    \Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\PhoneMetadataProvider::class,
    MyLeanCountryProvider::class,
);
```

The bundled defaults — `LibPhoneNumberProvider`, `PhoneNumberFormatter`, `PhoneNumberNormalizer`,
`PhoneNumberValidator` — are wired so overriding one upstream flows through the rest (override
just the provider and the formatter and normalizer follow).

#### Accessibility & UX

The country selector is a keyboard-navigable combobox (`aria-haspopup`, arrow keys, type-to-
search, `Escape` to close); the national input uses `type="tel"` with `inputmode="tel"` for the
right mobile keyboard. The control is theme-aware (native light/dark), RTL-safe (logical
properties throughout), and honors `forced-colors` / high-contrast mode. Flags are pure emoji
derived from the ISO code, so the package ships no image assets and makes no sprite requests.

#### Performance

With the default deferred binding, typing costs **zero** Livewire round-trips — the client owns
all interaction and syncs the composed value only when the form reads it. The country catalog is
resolved once per render and memoized per locale; example numbers are memoized in the provider
and built only when something actually needs them. The Alpine layer reads a single pre-computed
`PhoneViewModel`, so no configuration is ever re-derived in JavaScript.

### DiffField

A GitHub-style unified diff view as a form field: file header with additions/deletions stat
squares, dual line-number gutters, red/green row backgrounds, and long runs of unchanged context
collapsed behind an "Expand N hidden lines" toggle.

The field diffs two raw strings itself — you hand it the old and new text, not pre-computed diff
data. Line comparison is powered by [`sebastian/diff`](https://github.com/sebastianbergmann/diff),
which Composer pulls in automatically with this package (it is the package's only non-Filament
runtime dependency, and it performs stateless in-memory diffing only — no persistence, no models).

#### Quick start

```php
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\DiffField;

DiffField::make('changes')
    ->filename('config/app.php')
    ->oldValue(fn (Revision $record): string => $record->previous_content)
    ->newValue(fn (Revision $record): string => $record->content);
```

`oldValue()` and `newValue()` accept a plain string or a closure (with the usual Filament
injections — `$record`, `$get`, ...), so the diff can be static or recomputed live as other
fields change.

#### Options

- `contextLines(int | Closure $lines = 3)` — how many unchanged lines stay visible around each
  change. Any longer run of unchanged context is grouped into a single collapsed block; clicking
  it reveals the hidden rows instantly (they are already in the DOM, toggled by Alpine — no
  Livewire round-trip).
- `filename(string | Closure | null $name = null)` — the name shown in the diff header. When
  omitted, the header falls back to the field's label.

#### Empty values

If `oldValue()` or `newValue()` resolves to an empty string (or is never set), there is nothing
meaningful to diff from or to, so the field renders a "No changes to show." message instead of
treating the other side's content as an entirely added or deleted file.

#### Modal presentation

The line-diff table is built for whole files. For a single short value — a translation string, a
validation message — `->modal()` renders a compact trigger instead: clicking it opens the diff in
a native Filament action modal (built entirely from Filament's own action-modal machinery, so
there is no bespoke overlay markup to fight with your theme) with a **Side by Side** view (the
plain old/new text, each in its own box) and an **Inline** view (a single sentence with the changed
words struck through in red and the new words underlined in green), switched with a client-side
toggle:

```php
DiffField::make('message')
    ->modal()
    ->filename('validation.active_url')
    ->oldValue(fn (Translation $record): string => $record->getOriginal('value'))
    ->newValue(fn (Translation $record): string => $record->value);
```

Word-level comparison (used only by the Inline view) is computed separately from the line-diff
table by `WordDiffGenerator`, and — unlike the table's empty-value handling above — an empty side
diffs normally: an empty `oldValue()` reads as "this value was just added" (shown entirely
underlined), not as "nothing to compare."

Because the trigger already carries the heading, you'll usually want `->hiddenLabel()` alongside
`->modal()` so the field doesn't also print its own auto-generated label above it.

##### Rollback

Add a **Rollback** button to the modal's footer with `onRollback()`:

```php
DiffField::make('message')
    ->modal()
    ->oldValue(fn (Translation $record): string => $record->getOriginal('value'))
    ->newValue(fn (Translation $record): string => $record->value)
    ->onRollback(function (Translation $record, string $oldValue) {
        $record->update(['value' => $oldValue]);
    });
```

The callback is injected with `oldValue`, `newValue`, and the usual `$record`/`$get`/etc. The field
itself never touches persistence — without `onRollback()` the modal has no submit button at all;
registering one is what makes it appear, and committing the change is entirely the callback's job.

#### Display-only

`DiffField` extends Filament's `Field` for full schema integration (labels, helper text,
visibility closures), but it captures no user input: it is `dehydrated(false)` by default, so
nothing is ever written back into the form state on submit.

Colors come from the panel's registered semantic palette (`--success-*`, `--danger-*`,
`--gray-*`) with automatic dark-mode variants, so the view respects your theme customization out
of the box.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [syriable](https://github.com/syriable)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
