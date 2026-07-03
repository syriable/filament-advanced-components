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

### MultiProgressColumn & MultiProgressEntry

A table column that renders a single progress bar divided into multiple colored segments —
like the per-language progress bars on translation dashboards such as Crowdin or Lokalise.

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

The only naming difference: segment spacing is `segmentGap()` (on both
components), because infolist entries inherit Filament's schema-level `gap()`
toggle. The table column additionally accepts `gap()` as an alias.

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
