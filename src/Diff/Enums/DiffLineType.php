<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums;

/**
 * The three kinds of row a unified diff can render: unchanged context,
 * a line added by the new revision, or a line removed from the old one.
 */
enum DiffLineType: string
{
    case Context = 'context';
    case Addition = 'addition';
    case Deletion = 'deletion';

    /**
     * The single-character gutter prefix GitHub-style diffs put in front of
     * the content (` `, `+`, `-`).
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Context => ' ',
            self::Addition => '+',
            self::Deletion => '-',
        };
    }
}
