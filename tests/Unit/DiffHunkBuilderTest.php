<?php

declare(strict_types=1);

use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffHunk;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffLine;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\DiffHunkBuilder;

/**
 * Builds a flat DiffLine list from a compact spec string where each
 * character is a line: `c` = context, `a` = addition, `d` = deletion.
 * Old/new line counters advance exactly as DiffGenerator's would.
 *
 * @return array<int, DiffLine>
 */
function diffLinesFromSpec(string $spec): array
{
    $lines = [];
    $oldNo = 1;
    $newNo = 1;

    foreach (str_split($spec) as $index => $char) {
        $lines[] = match ($char) {
            'c' => new DiffLine(DiffLineType::Context, $oldNo++, $newNo++, "content {$index}"),
            'a' => new DiffLine(DiffLineType::Addition, null, $newNo++, "content {$index}"),
            'd' => new DiffLine(DiffLineType::Deletion, $oldNo++, null, "content {$index}"),
        };
    }

    return $lines;
}

/**
 * @param  array<int, DiffHunk>  $hunks
 * @return list<string> e.g. ['visible:5', 'collapsed:7', 'visible:4']
 */
function hunkShapes(array $hunks): array
{
    return array_map(
        fn (DiffHunk $hunk): string => ($hunk->isCollapsed ? 'collapsed:' : 'visible:') . count($hunk->lines),
        $hunks,
    );
}

describe('context window boundaries', function () {
    it('does not collapse exactly 2 * contextLines unchanged lines between two changes', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('a' . str_repeat('c', 6) . 'd'), 3);

        expect(hunkShapes($hunks))->toBe(['visible:8']);
    });

    it('collapses 2 * contextLines + 1 unchanged lines between two changes', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('a' . str_repeat('c', 7) . 'd'), 3);

        expect(hunkShapes($hunks))->toBe(['visible:4', 'collapsed:1', 'visible:4'])
            ->and($hunks[1]->hiddenCount)->toBe(1);
    });

    it('keeps contextLines of context on each side of a collapsed middle run', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('a' . str_repeat('c', 10) . 'd'), 2);

        expect(hunkShapes($hunks))->toBe(['visible:3', 'collapsed:6', 'visible:3']);
    });
});

describe('file boundaries', function () {
    it('collapses leading context beyond the window, keeping contextLines before the first change', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec(str_repeat('c', 8) . 'a'), 3);

        expect(hunkShapes($hunks))->toBe(['collapsed:5', 'visible:4'])
            ->and($hunks[0]->hiddenOldStart)->toBe(1)
            ->and($hunks[0]->hiddenNewStart)->toBe(1);
    });

    it('collapses trailing context beyond the window, keeping contextLines after the last change', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('d' . str_repeat('c', 8)), 3);

        expect(hunkShapes($hunks))->toBe(['visible:4', 'collapsed:5'])
            ->and($hunks[1]->hiddenOldStart)->toBe(5)
            ->and($hunks[1]->hiddenNewStart)->toBe(4);
    });

    it('does not collapse leading context of exactly contextLines', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec(str_repeat('c', 3) . 'a'), 3);

        expect(hunkShapes($hunks))->toBe(['visible:4']);
    });

    it('collapses an all-context file longer than contextLines entirely', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec(str_repeat('c', 4)), 3);

        expect(hunkShapes($hunks))->toBe(['collapsed:4'])
            ->and($hunks[0]->hiddenCount)->toBe(4);
    });

    it('keeps an all-context file within the window fully visible', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec(str_repeat('c', 3)), 3);

        expect(hunkShapes($hunks))->toBe(['visible:3']);
    });
});

describe('hunk metadata', function () {
    it('records the hidden block starting line numbers for the expand label', function () {
        // Change at line 1, 9 context lines, change at line 11: lines 5-7
        // (old numbering) hide behind the toggle.
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('a' . str_repeat('c', 9) . 'd'), 3);

        expect(hunkShapes($hunks))->toBe(['visible:4', 'collapsed:3', 'visible:4'])
            ->and($hunks[1]->hiddenOldStart)->toBe(4)
            ->and($hunks[1]->hiddenNewStart)->toBe(5);
    });

    it('reports a zero hiddenCount on visible hunks', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('acd'), 3);

        expect($hunks)->toHaveCount(1)
            ->and($hunks[0]->hiddenCount)->toBe(0)
            ->and($hunks[0]->hiddenOldStart)->toBeNull()
            ->and($hunks[0]->hiddenNewStart)->toBeNull();
    });

    it('preserves line order and content across hunk boundaries', function () {
        $spec = 'a' . str_repeat('c', 7) . 'd';
        $lines = diffLinesFromSpec($spec);

        $hunks = DiffHunkBuilder::build($lines, 3);

        $flattened = array_merge(...array_map(fn (DiffHunk $hunk): array => $hunk->lines, $hunks));

        expect($flattened)->toBe($lines);
    });
});

describe('edge cases', function () {
    it('returns no hunks for an empty line list', function () {
        expect(DiffHunkBuilder::build([], 3))->toBe([]);
    });

    it('collapses every context run entirely when contextLines is zero', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('ccaccdcc'), 0);

        expect(hunkShapes($hunks))->toBe([
            'collapsed:2',
            'visible:1',
            'collapsed:2',
            'visible:1',
            'collapsed:2',
        ]);
    });

    it('produces a single visible hunk when everything changed', function () {
        $hunks = DiffHunkBuilder::build(diffLinesFromSpec('ddaa'), 3);

        expect(hunkShapes($hunks))->toBe(['visible:4']);
    });
});
