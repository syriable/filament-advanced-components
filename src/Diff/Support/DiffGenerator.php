<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Support;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffLine;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;

/**
 * Computes a renderable {@see DiffFile} from two raw strings. The line-level
 * comparison is delegated to sebastian/diff (stateless, in-memory); this
 * class assigns old/new line numbers, tallies the header stats, and hands
 * hunk grouping to {@see DiffHunkBuilder}.
 */
final class DiffGenerator
{
    public static function diff(
        string $old,
        string $new,
        int $contextLines = 3,
        ?string $filename = null,
    ): DiffFile {
        $contextLines = max(0, $contextLines);

        $differ = new Differ(new UnifiedDiffOutputBuilder);

        // Lines are split here rather than by the differ so that an empty
        // string means "zero lines" (a new or deleted file), not a file
        // containing one empty line — matching git's semantics.
        $tagged = $differ->diffToArray(self::splitLines($old), self::splitLines($new));

        $lines = [];
        $additions = 0;
        $deletions = 0;
        $oldLineNo = 1;
        $newLineNo = 1;

        foreach ($tagged as [$content, $tag]) {
            // Ignore the differ's non-line markers (e.g. line-ending
            // warnings); only real old/added/removed lines become rows.
            if ($tag === Differ::OLD) {
                $lines[] = new DiffLine(DiffLineType::Context, $oldLineNo++, $newLineNo++, (string) $content);
            } elseif ($tag === Differ::ADDED) {
                $additions++;
                $lines[] = new DiffLine(DiffLineType::Addition, null, $newLineNo++, (string) $content);
            } elseif ($tag === Differ::REMOVED) {
                $deletions++;
                $lines[] = new DiffLine(DiffLineType::Deletion, $oldLineNo++, null, (string) $content);
            }
        }

        return new DiffFile(
            filename: $filename,
            hunks: DiffHunkBuilder::build($lines, $contextLines),
            additionsCount: $additions,
            deletionsCount: $deletions,
        );
    }

    /**
     * Splits raw text into lines the way git counts them: CRLF/CR normalize
     * to LF, a trailing newline does not open a phantom last line, and an
     * empty string has no lines at all.
     *
     * @return list<string>
     */
    private static function splitLines(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);

        if (str_ends_with($normalized, "\n")) {
            $normalized = substr($normalized, 0, -1);
        }

        return explode("\n", $normalized);
    }
}
