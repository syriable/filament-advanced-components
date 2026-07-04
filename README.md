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

- *Light mode, default:* a slim pill-shaped bar inside the table cell. Green (Translated),
  amber (Needs Review), and red (Missing) blocks sit flush against each other, with the overall
  percentage in small gray tabular figures on the right. Hovering a block raises a Filament
  tooltip reading "**Translated** / 420 keys / 70%".
- *Dark mode:* the track becomes a translucent light-gray wash and each segment automatically
  switches to a shade with at least 3:1 (WCAG AA non-text) contrast against the dark surface.
- *With legend:* below the bar, a wrapping row of colored dots with labels, percentages, and
  optional count badges.
- *Striped / gradient:* diagonal translucent stripes, or a subtle left-to-right lightening
  gradient per segment.
- *Empty / loading:* an empty track with optional placeholder text, or a pulsing skeleton bar.

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

| Key | Type | Description |
| --- | --- | --- |
| `label` | `string` | Used in tooltips, the legend, and ARIA labels. |
| `value` | `int\|float` | Raw amount or percentage; normalized against the total. |
| `color` | `string\|array` | Semantic name, raw CSS color, or Filament palette (optional). |
| `tooltip` | `string\|Htmlable` | Overrides the generated tooltip (optional). |
| `icon` | `string\|BackedEnum` | Shown in the legend entry (optional). |
| `badge` | `string` | Small badge next to the legend entry (optional). |
| `url` | `string` | Makes the segment a clickable link (optional). |
| `shouldOpenUrlInNewTab` | `bool` | Defaults to `false`. |

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
  maps, picking a shade with WCAG AA non-text contrast (3:1) against the track in *both* light
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

Because the bar is plain server-rendered Blade, it updates with *anything* that re-renders
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
> Can't run a WebSocket process where you deploy? The lightweight fallback is *conditional*
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
by the parent itself and only *wrapped* when an advanced feature needs it.

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

Render an image or icon on either side of the content — including a prefix *and* a suffix
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

Beyond the native `badge()` (which turns the *state itself* into badges), an unlimited
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
    "packages": [
        {"id": "9c2e…", "title": "Starter", "meta": {}}
    ],
    "rows": [
        {"id": "d7f3…", "label": "Responsive Design", "type": "boolean",
         "config": {}, "values": {"9c2e…": true}}
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
- **The server never trusts the browser.** On hydration *and* dehydration the payload is
  re-normalized: unknown row types are dropped, orphaned cell values pruned, per-type
  config whitelisted, every cell value coerced through its row type, and
  `minPackages()` / `maxPackages()` are enforced as validation rules.
- **UX**: sticky feature column, horizontal scrolling as packages grow, inline renames,
  a per-row settings popover (options for select/radio rows, currency for price rows),
  collapsible editor, full dark-mode styling, keyboard-accessible controls.

#### Built-in row types

| Type | Cell editor | Per-row settings |
| --- | --- | --- |
| `boolean` | Checkmark | — |
| `text` / `textarea` / `description` | Text input / textarea | — |
| `number` | Number input | — |
| `price` | Number input with currency prefix | Currency |
| `select` / `radio` | Dropdown / radio group | Options list |
| `delivery` | Amount + unit (`{"amount": 7, "unit": "days"}`) | — |
| `footer` | Per-package call-to-action line | — |

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
