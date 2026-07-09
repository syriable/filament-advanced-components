<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Support;

use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffHunk;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffLine;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;

/**
 * Groups a flat, ordered list of {@see DiffLine}s into renderable hunks,
 * collapsing long runs of unchanged context the way git does: keep
 * `$contextLines` of context on each side that touches a change, hide the
 * middle behind a single expandable block. Context that touches a file
 * boundary instead of a change keeps nothing on that side.
 */
final class DiffHunkBuilder
{
    /**
     * @param  array<int, DiffLine>  $lines
     * @return array<int, DiffHunk>
     */
    public static function build(array $lines, int $contextLines): array
    {
        $lines = array_values($lines);

        if ($lines === []) {
            return [];
        }

        $hunks = [];

        /** @var array<int, DiffLine> $visible Visible lines accumulated for the current normal hunk. */
        $visible = [];

        foreach (self::runs($lines) as $run) {
            [$isContext, $runLines, $isFirst, $isLast] = $run;

            if (! $isContext) {
                $visible = [...$visible, ...$runLines];

                continue;
            }

            $keepHead = $isFirst ? 0 : $contextLines;
            $keepTail = $isLast ? 0 : $contextLines;
            $length = count($runLines);

            // A run only collapses when hiding it actually saves rows beyond
            // the kept context. For a run touching changes on both sides that
            // is `2 * $contextLines`; for boundary runs (including a fully
            // unchanged file) the threshold floors at `$contextLines`, so
            // e.g. identical short inputs render as-is instead of hiding a
            // couple of lines behind a toggle.
            if ($length <= max($keepHead + $keepTail, $contextLines)) {
                $visible = [...$visible, ...$runLines];

                continue;
            }

            $visible = [...$visible, ...array_slice($runLines, 0, $keepHead)];

            if ($visible !== []) {
                $hunks[] = new DiffHunk(lines: $visible);
                $visible = [];
            }

            $hidden = array_slice($runLines, $keepHead, $length - $keepHead - $keepTail);

            $hunks[] = new DiffHunk(
                lines: $hidden,
                isCollapsed: true,
                hiddenCount: count($hidden),
                hiddenOldStart: $hidden[0]->oldLineNo,
                hiddenNewStart: $hidden[0]->newLineNo,
            );

            if ($keepTail > 0) {
                $visible = array_slice($runLines, $length - $keepTail);
            }
        }

        if ($visible !== []) {
            $hunks[] = new DiffHunk(lines: $visible);
        }

        return $hunks;
    }

    /**
     * Splits the flat line list into maximal runs of same-kind lines
     * (context vs. change), tagging each run with whether it sits at the
     * start and/or end of the file.
     *
     * @param  array<int, DiffLine>  $lines
     * @return list<array{bool, array<int, DiffLine>, bool, bool}>
     */
    private static function runs(array $lines): array
    {
        $runs = [];
        $current = [];
        $currentIsContext = null;

        foreach ($lines as $line) {
            $isContext = $line->type === DiffLineType::Context;

            if ($currentIsContext !== null && $isContext !== $currentIsContext) {
                $runs[] = [$currentIsContext, $current];
                $current = [];
            }

            $current[] = $line;
            $currentIsContext = $isContext;
        }

        if ($current !== []) {
            $runs[] = [$currentIsContext === true, $current];
        }

        $lastIndex = count($runs) - 1;

        return array_map(
            static fn (array $run, int $index): array => [$run[0], $run[1], $index === 0, $index === $lastIndex],
            $runs,
            array_keys($runs),
        );
    }
}
