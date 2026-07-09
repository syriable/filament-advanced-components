<?php

declare(strict_types=1);

use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffLine;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\DiffGenerator;

/**
 * Flattens a DiffFile's hunks back into a single ordered line list, so
 * assertions can address lines without caring about hunk boundaries.
 *
 * @return array<int, DiffLine>
 */
function allDiffLines(DiffFile $file): array
{
    return array_merge(...array_map(fn ($hunk) => $hunk->lines, $file->hunks) ?: [[]]);
}

describe('identical inputs', function () {
    it('reports no additions or deletions', function () {
        $text = "alpha\nbravo\ncharlie";

        $file = DiffGenerator::diff($text, $text);

        expect($file->additionsCount)->toBe(0)
            ->and($file->deletionsCount)->toBe(0)
            ->and($file->hunks)->toHaveCount(1)
            ->and(allDiffLines($file))->toHaveCount(3)
            ->and(allDiffLines($file)[0]->type)->toBe(DiffLineType::Context);
    });

    it('fully collapses an identical file longer than the context window', function () {
        $text = implode("\n", array_map(fn (int $i): string => "line {$i}", range(1, 10)));

        $file = DiffGenerator::diff($text, $text, contextLines: 3);

        expect($file->hunks)->toHaveCount(1)
            ->and($file->hunks[0]->isCollapsed)->toBeTrue()
            ->and($file->hunks[0]->hiddenCount)->toBe(10)
            ->and($file->hunks[0]->hiddenOldStart)->toBe(1)
            ->and($file->hunks[0]->hiddenNewStart)->toBe(1);
    });

    it('keeps a short identical file visible instead of collapsing it', function () {
        $file = DiffGenerator::diff("one\ntwo", "one\ntwo", contextLines: 3);

        expect($file->hunks)->toHaveCount(1)
            ->and($file->hunks[0]->isCollapsed)->toBeFalse();
    });
});

describe('additions, deletions, and mixed changes', function () {
    it('handles a pure addition', function () {
        $file = DiffGenerator::diff("one\nthree", "one\ntwo\nthree");

        $lines = allDiffLines($file);

        expect($file->additionsCount)->toBe(1)
            ->and($file->deletionsCount)->toBe(0)
            ->and($lines)->toHaveCount(3)
            ->and($lines[1]->type)->toBe(DiffLineType::Addition)
            ->and($lines[1]->oldLineNo)->toBeNull()
            ->and($lines[1]->newLineNo)->toBe(2)
            ->and($lines[1]->content)->toBe('two')
            ->and($lines[2]->oldLineNo)->toBe(2)
            ->and($lines[2]->newLineNo)->toBe(3);
    });

    it('handles a pure deletion', function () {
        $file = DiffGenerator::diff("one\ntwo\nthree", "one\nthree");

        $lines = allDiffLines($file);

        expect($file->additionsCount)->toBe(0)
            ->and($file->deletionsCount)->toBe(1)
            ->and($lines[1]->type)->toBe(DiffLineType::Deletion)
            ->and($lines[1]->oldLineNo)->toBe(2)
            ->and($lines[1]->newLineNo)->toBeNull()
            ->and($lines[2]->oldLineNo)->toBe(3)
            ->and($lines[2]->newLineNo)->toBe(2);
    });

    it('handles a mixed change as a deletion plus an addition', function () {
        $file = DiffGenerator::diff("one\ntwo\nthree", "one\n2\nthree");

        $lines = allDiffLines($file);
        $types = array_map(fn (DiffLine $line): DiffLineType => $line->type, $lines);

        expect($file->additionsCount)->toBe(1)
            ->and($file->deletionsCount)->toBe(1)
            ->and($types)->toBe([
                DiffLineType::Context,
                DiffLineType::Deletion,
                DiffLineType::Addition,
                DiffLineType::Context,
            ]);
    });
});

describe('empty inputs', function () {
    it('treats an empty old string as a new file of pure additions', function () {
        $file = DiffGenerator::diff('', "one\ntwo");

        $lines = allDiffLines($file);

        expect($file->additionsCount)->toBe(2)
            ->and($file->deletionsCount)->toBe(0)
            ->and($lines)->toHaveCount(2)
            ->and($lines[0]->type)->toBe(DiffLineType::Addition)
            ->and($lines[0]->oldLineNo)->toBeNull()
            ->and($lines[0]->newLineNo)->toBe(1)
            ->and($lines[1]->newLineNo)->toBe(2);
    });

    it('treats an empty new string as a deleted file of pure deletions', function () {
        $file = DiffGenerator::diff("one\ntwo", '');

        $lines = allDiffLines($file);

        expect($file->additionsCount)->toBe(0)
            ->and($file->deletionsCount)->toBe(2)
            ->and($lines)->toHaveCount(2)
            ->and($lines[0]->type)->toBe(DiffLineType::Deletion)
            ->and($lines[0]->oldLineNo)->toBe(1)
            ->and($lines[0]->newLineNo)->toBeNull();
    });

    it('produces no hunks when both strings are empty', function () {
        $file = DiffGenerator::diff('', '');

        expect($file->hunks)->toBe([])
            ->and($file->additionsCount)->toBe(0)
            ->and($file->deletionsCount)->toBe(0);
    });
});

describe('line numbering across hunks', function () {
    it('keeps old and new counters correctly offset across multiple changes', function () {
        // Insert after line 2, delete old line 12: past the insertion the new
        // numbers run one ahead, past the deletion they realign.
        $old = implode("\n", array_map(fn (int $i): string => "line {$i}", range(1, 20)));
        $newLines = array_map(fn (int $i): string => "line {$i}", range(1, 20));
        array_splice($newLines, 2, 0, ['inserted']);
        unset($newLines[12]); // "line 12" (index 12 after the insertion)
        $new = implode("\n", array_values($newLines));

        $file = DiffGenerator::diff($old, $new, contextLines: 2);

        $lines = allDiffLines($file);

        $inserted = array_values(array_filter($lines, fn (DiffLine $line): bool => $line->type === DiffLineType::Addition))[0];
        $deleted = array_values(array_filter($lines, fn (DiffLine $line): bool => $line->type === DiffLineType::Deletion))[0];
        $last = $lines[count($lines) - 1];

        expect($inserted->newLineNo)->toBe(3)
            ->and($inserted->oldLineNo)->toBeNull()
            ->and($deleted->content)->toBe('line 12')
            ->and($deleted->oldLineNo)->toBe(12)
            ->and($deleted->newLineNo)->toBeNull()
            ->and($last->content)->toBe('line 20')
            ->and($last->oldLineNo)->toBe(20)
            ->and($last->newLineNo)->toBe(20);
    });
});

describe('metadata', function () {
    it('carries the filename through to the DiffFile', function () {
        $file = DiffGenerator::diff('a', 'b', filename: 'config/app.php');

        expect($file->filename)->toBe('config/app.php');
    });

    it('normalizes CRLF line endings and ignores a trailing newline', function () {
        $file = DiffGenerator::diff("one\r\ntwo\r\n", "one\ntwo\n");

        expect($file->additionsCount)->toBe(0)
            ->and($file->deletionsCount)->toBe(0)
            ->and(allDiffLines($file))->toHaveCount(2);
    });
});
