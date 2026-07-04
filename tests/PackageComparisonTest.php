<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PackageComparison;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\BooleanRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\PriceRow;
use Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes\RowType;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    // Laravel's ShareErrorsFromSession middleware shares this with every view
    // in a real request; Filament's field wrapper expects it, so standalone
    // renders in tests need it too.
    View::share('errors', new ViewErrorBag);
});

it('seeds default packages and rows on an empty state', function () {
    $field = PackageComparison::make('packages')
        ->defaultPackages(3)
        ->defaultRows([
            ['label' => 'Responsive Design', 'type' => 'boolean'],
            ['label' => 'Price', 'type' => 'price', 'config' => ['currency' => 'eur']],
        ]);

    $state = $field->normalizeState(null);

    expect($state['packages'])->toHaveCount(3)
        ->and(array_column($state['packages'], 'title'))->toBe(['Package A', 'Package B', 'Package C'])
        ->and($state['packages'][0]['id'])->toBeString()->not->toBeEmpty()
        ->and($state['rows'])->toHaveCount(2)
        ->and($state['rows'][0]['type'])->toBe('boolean')
        ->and($state['rows'][0]['values'])->toBe(array_fill_keys(array_column($state['packages'], 'id'), false))
        ->and($state['rows'][1]['config']['currency'])->toBe('EUR');
});

it('never trusts the browser payload', function () {
    $packageId = (string) Str::uuid();

    $state = PackageComparison::make('packages')->normalizeState([
        'packages' => [
            ['id' => $packageId, 'title' => '  Starter  '],
            ['id' => $packageId, 'title' => ''], // duplicate id, blank title
            'not-an-array',
        ],
        'rows' => [
            [
                'id' => 'row-1',
                'label' => 'Products',
                'type' => 'number',
                'config' => ['min' => 0, 'unknown_key' => 'dropped'],
                'values' => [
                    $packageId => '-5', // clamped to min
                    'deleted-package' => 10, // orphan, pruned
                ],
            ],
            ['id' => 'row-2', 'label' => 'Evil', 'type' => 'not-a-type', 'values' => []],
        ],
    ]);

    $packageIds = array_column($state['packages'], 'id');

    expect($state['packages'])->toHaveCount(2)
        ->and($state['packages'][0]['title'])->toBe('Starter')
        ->and($state['packages'][1]['id'])->not->toBe($packageId)
        ->and($state['packages'][1]['title'])->toBe('Package B')
        ->and($state['rows'])->toHaveCount(1)
        ->and($state['rows'][0]['config'])->not->toHaveKey('unknown_key')
        ->and($state['rows'][0]['values'])->toHaveKeys($packageIds)
        ->and($state['rows'][0]['values'][$packageId])->toBe(0)
        ->and($state['rows'][0]['values'])->not->toHaveKey('deleted-package');
});

it('coerces cell values through each row type strategy', function () {
    $field = PackageComparison::make('packages');

    $boolean = $field->getRowType('boolean');
    $select = $field->getRowType('select');
    $delivery = $field->getRowType('delivery');

    expect($boolean->normalizeValue('yes', []))->toBeTrue()
        ->and($boolean->normalizeValue('nonsense', []))->toBeFalse()
        ->and($select->normalizeValue('Gold', ['options' => ['Gold', 'Silver']]))->toBe('Gold')
        ->and($select->normalizeValue('Injected', ['options' => ['Gold', 'Silver']]))->toBeNull()
        ->and($delivery->normalizeValue(['amount' => '7', 'unit' => 'days'], ['units' => ['days', 'hours']]))->toBe(['amount' => 7, 'unit' => 'days'])
        ->and($delivery->normalizeValue('garbage', ['units' => ['days', 'hours']]))->toBe(['amount' => null, 'unit' => 'days']);
});

it('supports user-defined row types without touching the core', function () {
    $ratingRow = new class extends RowType
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
            return 'filament-advanced-components::components.package-comparison.cells.number';
        }
    };

    $field = PackageComparison::make('packages')
        ->allowedRowTypes([BooleanRow::class, $ratingRow::class]);

    $state = $field->normalizeState([
        'packages' => [['id' => 'p1', 'title' => 'Starter']],
        'rows' => [
            ['id' => 'r1', 'label' => 'Rating', 'type' => 'rating', 'values' => ['p1' => 99]],
        ],
    ]);

    expect($field->getRowTypes())->toHaveKeys(['boolean', 'rating'])
        ->and($field->getRowType('rating')->getLabel())->toBe('Rating')
        ->and($state['rows'][0]['values']['p1'])->toBe(5);
});

it('clamps the package count options against each other', function () {
    $field = PackageComparison::make('packages')
        ->minPackages(fn (): int => 2)
        ->maxPackages(4)
        ->defaultPackages(10);

    expect($field->getMinPackages())->toBe(2)
        ->and($field->getMaxPackages())->toBe(4)
        ->and($field->getDefaultPackages())->toBe(4)
        ->and($field->normalizeState(['packages' => [['title' => 'Solo']], 'rows' => []])['packages'])->toHaveCount(2);
});

it('rejects package counts outside the configured bounds', function () {
    $field = PackageComparison::make('packages')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->minPackages(2)
        ->maxPackages(3);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    expect($rule)->not->toBeNull();

    $failures = [];
    $fail = function (string $message) use (&$failures): void {
        $failures[] = $message;
    };

    $rule('packages', $field->normalizeState(null), $fail);
    expect($failures)->toBeEmpty();

    $rule('packages', ['packages' => [['id' => 'a']], 'rows' => []], $fail);
    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain('2');

    $failures = [];
    $rule('packages', ['packages' => array_fill(0, 5, ['id' => 'x']), 'rows' => []], $fail);
    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain('3');
});

it('renders the editor inside the field wrapper with one template per allowed type', function () {
    $html = PackageComparison::make('packages')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->label('Packages')
        ->allowedRowTypes([BooleanRow::class, PriceRow::class])
        ->toHtml();

    expect($html)->toContain('fi-fo-package-comparison-wrp')
        ->and($html)->toContain('packageComparison({')
        ->and($html)->toContain('x-load-src')
        ->and($html)->toContain('wire:ignore')
        ->and($html)->toContain('x-sortable')
        ->and($html)->toContain('Add feature')
        ->and($html)->toContain('Add package')
        ->and($html)->toContain('row.type === \'boolean\'')
        ->and($html)->toContain('row.type === \'price\'')
        ->and($html)->not->toContain('row.type === \'select\'')
        ->and($html)->toContain('Checkmark')
        ->and($html)->toContain('--fi-pc-feature-col-w: 14rem');
});

it('renders no mutating controls when disabled', function () {
    $html = PackageComparison::make('packages')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->disabled()
        ->toHtml();

    expect($html)->not->toContain('Add package')
        ->and($html)->not->toContain('addRow(')
        ->and($html)->not->toContain('x-sortable-handle')
        ->and($html)->toContain('fi-disabled');
});

it('exposes descriptors the Alpine layer can seed from', function () {
    $descriptors = PackageComparison::make('packages')->getRowTypeDescriptors();

    expect($descriptors)->toHaveCount(10)
        ->and($descriptors['boolean'])->toBe([
            'name' => 'boolean',
            'label' => 'Checkmark',
            'default' => false,
            'config' => [],
            'hasSettings' => false,
        ])
        ->and($descriptors['select']['hasSettings'])->toBeTrue()
        ->and($descriptors['price']['config']['currency'])->toBe('USD')
        ->and($descriptors['delivery']['default'])->toBe(['amount' => null, 'unit' => 'days']);
});
