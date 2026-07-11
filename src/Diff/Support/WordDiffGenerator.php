<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Support;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\WordDiffToken;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;

/**
 * Computes a word-level diff between two short strings (e.g. a translation
 * value or a validation message) as a flat, ordered list of
 * {@see WordDiffToken}s, for rendering as one inline sentence with
 * strikethrough/underline spans rather than a line-by-line table.
 *
 * Unlike {@see DiffGenerator}, an empty side is diffed normally: the other
 * side's content simply comes back as entirely added or removed, which is
 * the expected reading for a short value ("this was just added"/"this was
 * removed"), not a data-completeness gap the way a missing large text blob
 * usually is.
 */
final class WordDiffGenerator
{
    /**
     * @return array<int, WordDiffToken>
     */
    public static function diff(string $old, string $new): array
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder);
        $tagged = $differ->diffToArray(self::tokenize($old), self::tokenize($new));

        $tokens = [];
        $bufferType = null;
        $buffer = '';

        foreach ($tagged as [$text, $tag]) {
            $type = match (true) {
                $tag === Differ::OLD => DiffLineType::Context,
                $tag === Differ::ADDED => DiffLineType::Addition,
                $tag === Differ::REMOVED => DiffLineType::Deletion,
                // Ignore the differ's non-line markers (e.g. line-ending
                // warnings); only real unchanged/added/removed tokens count.
                default => null,
            };

            if ($type === null) {
                continue;
            }

            if ($type === $bufferType) {
                $buffer .= $text;

                continue;
            }

            if ($bufferType !== null) {
                $tokens[] = new WordDiffToken($bufferType, $buffer);
            }

            $bufferType = $type;
            $buffer = (string) $text;
        }

        if ($bufferType !== null) {
            $tokens[] = new WordDiffToken($bufferType, $buffer);
        }

        return $tokens;
    }

    /**
     * Splits text into words and whitespace runs, both kept as separate
     * tokens, so reassembling the tokens in order reproduces the original
     * string exactly and whitespace-only differences don't themselves
     * register as changes when the surrounding words match.
     *
     * @return list<string>
     */
    private static function tokenize(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
