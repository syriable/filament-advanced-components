<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\MultiProgressEntry;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Segment;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

it('normalizes raw values into percentages against an explicit total', function () {
    $entry = MultiProgressEntry::make('progress')
        ->segments([
            ['label' => 'Translated', 'value' => 420, 'color' => 'success'],
            ['label' => 'Needs Review', 'value' => 120, 'color' => 'warning'],
        ])
        ->total(600)
        ->showPercentage();

    $data = $entry->getProgressData();

    expect($data['isEmpty'])->toBeFalse()
        ->and($data['segments'][0]['percentage'])->toBe(70.0)
        ->and($data['segments'][1]['percentage'])->toBe(20.0)
        ->and($data['percentage'])->toBe(90.0)
        ->and($data['formattedPercentage'])->toBe('90%');
});

it('accepts segment value objects and resolves colors', function () {
    $entry = MultiProgressEntry::make('progress')
        ->segments([
            Segment::make('Approved')->value(80)->color('info'),
            ['label' => 'Raw hex', 'value' => 20, 'color' => '#8b5cf6'],
        ]);

    $segments = $entry->getProgressData()['segments'];

    expect($segments[0]['color']['classes'])->toContain('fi-color-info')
        ->and($segments[1]['color']['styles'])->toContain('--bg: #8b5cf6');
});

it('generates tooltips identically to the column', function () {
    $entry = MultiProgressEntry::make('progress')
        ->valueSuffix('keys')
        ->segments([['label' => 'Translated', 'value' => 420, 'color' => 'success']])
        ->total(600);

    $tooltip = $entry->getProgressData()['segments'][0]['tooltip'];

    expect($tooltip)->toBeInstanceOf(HtmlString::class)
        ->and($tooltip->toHtml())->toContain('<strong>Translated</strong>')
        ->and($tooltip->toHtml())->toContain('420 keys')
        ->and($tooltip->toHtml())->toContain('70%');
});

it('renders the progress bar view', function () {
    $html = MultiProgressEntry::make('progress')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->segments([
            ['label' => 'Translated', 'value' => 420, 'color' => 'success'],
            ['label' => 'Missing', 'value' => 180, 'color' => 'danger'],
        ])
        ->showPercentage()
        ->showLegend()
        ->toHtml();

    expect($html)->toContain('fi-multi-progress-track')
        ->and($html)->toContain('width: 70%')
        ->and($html)->toContain('width: 30%')
        ->and($html)->toContain('fi-color-success')
        ->and($html)->toContain('fi-color-danger')
        ->and($html)->toContain('x-tooltip')
        ->and($html)->toContain('fi-multi-progress-legend')
        ->and($html)->toContain('Translated: 70%, Missing: 30%')
        ->and($html)->toContain('100%');
});

it('renders a skeleton when empty and enabled', function () {
    $html = MultiProgressEntry::make('progress')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->segments([])
        ->skeleton()
        ->toHtml();

    expect($html)->toContain('fi-multi-progress-skeleton');
});

it('supports the shared appearance API', function () {
    $entry = MultiProgressEntry::make('progress')
        ->size('lg')
        ->segmentGap(2)
        ->borderRadius(4);

    expect($entry->getHeight())->toBe('1rem')
        ->and($entry->getGap())->toBe(2)
        ->and($entry->getBorderRadius())->toBe('4px');
});
