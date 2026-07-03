<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgress\Segment;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgressColumn;

it('normalizes raw values into percentages against the sum', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Translated', 'value' => 420, 'color' => 'success'],
            ['label' => 'Needs Review', 'value' => 120, 'color' => 'warning'],
            ['label' => 'Missing', 'value' => 60, 'color' => 'danger'],
        ]);

    $data = $column->getProgressData();

    expect($data['isEmpty'])->toBeFalse()
        ->and($data['segments'][0]['percentage'])->toBe(70.0)
        ->and($data['segments'][1]['percentage'])->toBe(20.0)
        ->and($data['segments'][2]['percentage'])->toBe(10.0)
        ->and($data['percentage'])->toBe(100.0);
});

it('treats an explicit total as the denominator and leaves a remainder', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Done', 'value' => 300, 'color' => 'success'],
        ])
        ->total(600)
        ->showPercentage();

    $data = $column->getProgressData();

    expect($data['segments'][0]['percentage'])->toBe(50.0)
        ->and($data['percentage'])->toBe(50.0)
        ->and($data['formattedPercentage'])->toBe('50%');
});

it('clamps an overflowing total to the sum of the segments', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'A', 'value' => 150],
            ['label' => 'B', 'value' => 50],
        ])
        ->total(100);

    $data = $column->getProgressData();

    expect($data['segments'][0]['percentage'])->toBe(75.0)
        ->and($data['segments'][1]['percentage'])->toBe(25.0)
        ->and($data['percentage'])->toBe(100.0);
});

it('evaluates closures for segments and total', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments(fn (): array => [
            ['label' => 'Done', 'value' => 25],
        ])
        ->total(fn (): int => 100);

    expect($column->getProgressData()['segments'][0]['percentage'])->toBe(25.0);
});

it('accepts segment value objects', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            Segment::make('Approved')
                ->value(80)
                ->color('info')
                ->badge('80')
                ->url('https://example.com', shouldOpenInNewTab: true),
            Segment::make('Rest')->value(20),
        ]);

    $segment = $column->getProgressData()['segments'][0];

    expect($segment['label'])->toBe('Approved')
        ->and($segment['percentage'])->toBe(80.0)
        ->and($segment['badge'])->toBe('80')
        ->and($segment['url'])->toBe('https://example.com')
        ->and($segment['shouldOpenUrlInNewTab'])->toBeTrue()
        ->and($segment['color']['classes'])->toContain('fi-color-info');
});

it('enforces a minimum visible segment width by shrinking larger segments', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Big', 'value' => 99.5],
            ['label' => 'Tiny', 'value' => 0.5],
        ])
        ->minSegmentWidth(2);

    $segments = $column->getProgressData()['segments'];

    expect($segments[1]['width'])->toBe(2.0)
        ->and($segments[0]['width'])->toBe(98.0)
        // The true percentage is preserved for tooltips and ARIA labels.
        ->and($segments[1]['percentage'])->toBe(0.5)
        ->and($segments[0]['width'] + $segments[1]['width'])->toBeLessThanOrEqual(100.0);
});

it('resolves semantic colors into Filament utility classes', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([['label' => 'Done', 'value' => 1, 'color' => 'success']]);

    $color = $column->getProgressData()['segments'][0]['color'];

    expect($color['classes'])->toContain('fi-color', 'fi-color-success')
        ->and(implode(' ', $color['classes']))->toContain('fi-bg-color-')
        ->and($color['styles'])->toBeNull();
});

it('resolves raw CSS colors into inline custom properties', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([['label' => 'Done', 'value' => 1, 'color' => '#8b5cf6']]);

    $color = $column->getProgressData()['segments'][0]['color'];

    expect($color['classes'])->toContain('fi-color-custom')
        ->and($color['styles'])->toContain('--bg: #8b5cf6')
        ->and($color['styles'])->toContain('--dark-bg: #8b5cf6');
});

it('resolves palette arrays through the contrast-aware custom style resolver', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([['label' => 'Done', 'value' => 1, 'color' => Color::Purple]]);

    $color = $column->getProgressData()['segments'][0]['color'];

    expect($color['classes'])->toContain('fi-color-custom')
        ->and($color['styles'])->toContain('--bg: var(--color-')
        ->and($color['styles'])->toContain('--dark-bg: var(--color-');
});

it('cycles through fallback colors for segments without one', function () {
    $column = MultiProgressColumn::make('progress')
        ->fallbackColors(['success', 'danger'])
        ->segments([
            ['label' => 'A', 'value' => 1],
            ['label' => 'B', 'value' => 1],
            ['label' => 'C', 'value' => 1],
        ]);

    $segments = $column->getProgressData()['segments'];

    expect($segments[0]['color']['classes'])->toContain('fi-color-success')
        ->and($segments[1]['color']['classes'])->toContain('fi-color-danger')
        ->and($segments[2]['color']['classes'])->toContain('fi-color-success');
});

it('generates tooltips with the label, formatted value and percentage', function () {
    $column = MultiProgressColumn::make('progress')
        ->valueSuffix('keys')
        ->segments([['label' => 'Translated', 'value' => 420, 'color' => 'success']])
        ->total(600);

    $tooltip = $column->getProgressData()['segments'][0]['tooltip'];

    expect($tooltip)->toBeInstanceOf(HtmlString::class)
        ->and($tooltip->toHtml())->toContain('<strong>Translated</strong>')
        ->and($tooltip->toHtml())->toContain('420 keys')
        ->and($tooltip->toHtml())->toContain('70%');
});

it('lets a per-segment tooltip override the generated one', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([['label' => 'Done', 'value' => 1, 'tooltip' => 'Custom tooltip']]);

    expect($column->getProgressData()['segments'][0]['tooltip'])->toBe('Custom tooltip');
});

it('supports a global tooltip formatting callback', function () {
    $column = MultiProgressColumn::make('progress')
        ->formatSegmentTooltipUsing(fn (array $segment): string => "{$segment['label']} — {$segment['formattedPercentage']}")
        ->segments([['label' => 'Done', 'value' => 1]]);

    expect($column->getProgressData()['segments'][0]['tooltip'])->toBe('Done — 100%');
});

it('omits tooltips when they are disabled', function () {
    $column = MultiProgressColumn::make('progress')
        ->segmentTooltips(false)
        ->segments([['label' => 'Done', 'value' => 1]]);

    expect($column->getProgressData()['segments'][0]['tooltip'])->toBeNull();
});

it('supports value and percentage formatting callbacks', function () {
    $column = MultiProgressColumn::make('progress')
        ->formatValueUsing(fn (int | float $state): string => $state . ' items')
        ->formatPercentageUsing(fn (float $state): string => number_format($state, 2) . ' %')
        ->showPercentage()
        ->showTotal()
        ->segments([['label' => 'Done', 'value' => 5]])
        ->total(10);

    $data = $column->getProgressData();

    expect($data['formattedPercentage'])->toBe('50.00 %')
        ->and($data['formattedTotal'])->toBe('10 items');
});

it('reports an empty state for missing or zero-value segments', function () {
    expect(MultiProgressColumn::make('progress')->segments([])->getProgressData()['isEmpty'])->toBeTrue()
        ->and(MultiProgressColumn::make('progress')->segments([
            ['label' => 'Nothing', 'value' => 0],
        ])->getProgressData()['isEmpty'])->toBeTrue();
});

it('rejects segments without a numeric value', function () {
    MultiProgressColumn::make('progress')
        ->segments([['label' => 'Broken']])
        ->getProgressData();
})->throws(InvalidArgumentException::class);

it('builds a screen reader summary of all segments', function () {
    $column = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Translated', 'value' => 70],
            ['label' => 'Missing', 'value' => 30],
        ]);

    expect($column->getProgressData()['ariaLabel'])->toBe('Translated: 70%, Missing: 30%');
});

it('renders the progress bar view', function () {
    $html = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Translated', 'value' => 420, 'color' => 'success'],
            ['label' => 'Missing', 'value' => 180, 'color' => 'danger'],
        ])
        ->showPercentage()
        ->showLegend()
        ->toHtml();

    expect($html)->toContain('fi-ta-multi-progress-track')
        ->and($html)->toContain('width: 70%')
        ->and($html)->toContain('width: 30%')
        ->and($html)->toContain('fi-color-success')
        ->and($html)->toContain('fi-color-danger')
        ->and($html)->toContain('x-tooltip')
        ->and($html)->toContain('fi-ta-multi-progress-legend')
        ->and($html)->toContain('Translated: 70%, Missing: 30%')
        ->and($html)->toContain('100%');
});

it('renders clickable segments as links', function () {
    $html = MultiProgressColumn::make('progress')
        ->segments([
            ['label' => 'Done', 'value' => 1, 'url' => 'https://example.com', 'shouldOpenUrlInNewTab' => true],
        ])
        ->toHtml();

    expect($html)->toContain('href="https://example.com"')
        ->and($html)->toContain('target="_blank"');
});

it('renders a skeleton when empty and enabled', function () {
    $html = MultiProgressColumn::make('progress')
        ->segments([])
        ->skeleton()
        ->toHtml();

    expect($html)->toContain('fi-ta-multi-progress-skeleton');
});

it('applies size presets and explicit dimensions', function () {
    expect(MultiProgressColumn::make('progress')->getHeight())->toBe('0.625rem')
        ->and(MultiProgressColumn::make('progress')->size('lg')->getHeight())->toBe('1rem')
        ->and(MultiProgressColumn::make('progress')->height(4)->getHeight())->toBe('4px')
        ->and(MultiProgressColumn::make('progress')->height('1.5em')->getHeight())->toBe('1.5em')
        ->and(MultiProgressColumn::make('progress')->getBorderRadius())->toBe('calc(infinity * 1px)')
        ->and(MultiProgressColumn::make('progress')->borderRadius(2)->getBorderRadius())->toBe('2px')
        ->and(MultiProgressColumn::make('progress')->squared()->getBorderRadius())->toBe('0');
});

it('uses the compact height and hides the legend in compact mode', function () {
    $column = MultiProgressColumn::make('progress')
        ->compact()
        ->showLegend();

    expect($column->getHeight())->toBe('0.375rem')
        ->and($column->shouldShowLegend())->toBeFalse();
});
