<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\DiffField;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    // Laravel's ShareErrorsFromSession middleware shares this with every view
    // in a real request; Filament's field wrapper expects it, so standalone
    // renders in tests need it too.
    View::share('errors', new ViewErrorBag);
});

/**
 * A 20-line fixture with a change at line 5 and another at line 15. With the
 * default 3 context lines this yields five hunks: collapsed leading context,
 * the first change, collapsed middle context, the second change, and
 * collapsed trailing context.
 */
function diffFieldFixture(): DiffField
{
    $old = implode("\n", array_map(fn (int $i): string => "line {$i}", range(1, 20)));
    $new = str_replace(['line 5', 'line 15'], ['line five', 'line fifteen'], $old);

    return DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->oldValue($old)
        ->newValue($new);
}

it('renders the diff inside the field wrapper with header, gutters, and row backgrounds', function () {
    $html = diffFieldFixture()
        ->filename('config/app.php')
        ->toHtml();

    expect($html)->toContain('fi-fo-field')
        ->and($html)->toContain('config/app.php')
        ->and($html)->toContain('+2')
        ->and($html)->toContain('−2')
        ->and($html)->toContain('fi-diff-field-stat-square-addition')
        ->and($html)->toContain('fi-diff-field-row-addition')
        ->and($html)->toContain('fi-diff-field-row-deletion')
        ->and($html)->toContain('fi-diff-field-row-context')
        ->and($html)->toContain('line five')
        ->and($html)->toContain('line fifteen')
        ->and($html)->toContain('fi-diff-field-gutter');
});

it('renders the expected hunk structure with collapsed context blocks', function () {
    $html = diffFieldFixture()->toHtml();

    // Leading (1 hidden), middle (3 hidden), and trailing (2 hidden) context
    // blocks collapse; the two changed regions stay visible.
    expect(substr_count($html, '<tbody'))->toBe(5)
        ->and(substr_count($html, 'fi-diff-field-hunk-collapsed'))->toBe(3)
        ->and($html)->toContain('Expand 1 hidden line')
        ->and($html)->toContain('Expand 3 hidden lines')
        ->and($html)->toContain('Expand 2 hidden lines')
        ->and(substr_count($html, 'x-data="{ expanded: false }"'))->toBe(3);
});

it('shows the filename in the header, falling back to the field label', function () {
    $withFilename = diffFieldFixture()->filename('config/app.php')->toHtml();

    expect($withFilename)->toContain('fi-diff-field-filename">config/app.php<');

    $labelled = diffFieldFixture()->label('Configuration changes')->toHtml();

    expect($labelled)->toContain('fi-diff-field-filename">Configuration changes<');
});

it('evaluates closure-based configuration', function () {
    $field = DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->oldValue(fn (): string => "one\ntwo")
        ->newValue(fn (): string => "one\n2")
        ->contextLines(fn (): int => 1)
        ->filename(fn (): string => 'closure.txt');

    $diffFile = $field->getDiffFile();

    expect($diffFile->filename)->toBe('closure.txt')
        ->and($diffFile->additionsCount)->toBe(1)
        ->and($diffFile->deletionsCount)->toBe(1);
});

it('memoizes the computed diff per request', function () {
    $field = diffFieldFixture();

    expect($field->getDiffFile())->toBeInstanceOf(DiffFile::class)
        ->and($field->getDiffFile())->toBe($field->getDiffFile());
});

it('recomputes the diff when reconfigured after a first computation', function () {
    $field = diffFieldFixture();
    $before = $field->getDiffFile();

    $field->newValue("completely\ndifferent");

    expect($field->getDiffFile())->not->toBe($before);
});

it('is never dehydrated into the form payload', function () {
    $field = DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent));

    expect($field->isDehydrated())->toBeFalse();
});
