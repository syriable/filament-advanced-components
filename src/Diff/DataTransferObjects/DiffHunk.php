<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects;

/**
 * A contiguous block of diff rows. A normal hunk holds visible changed lines
 * plus their kept context; a collapsed hunk holds a run of unchanged context
 * that starts hidden and is expanded client-side (the lines are already in
 * the DOM, just not shown).
 */
final readonly class DiffHunk
{
    /**
     * @param  array<int, DiffLine>  $lines
     */
    public function __construct(
        public array $lines,
        public bool $isCollapsed = false,
        /** Number of hidden lines; always 0 when {@see $isCollapsed} is false. */
        public int $hiddenCount = 0,
        /** Old-revision line number the hidden block starts at, for the expand label. */
        public ?int $hiddenOldStart = null,
        /** New-revision line number the hidden block starts at, for the expand label. */
        public ?int $hiddenNewStart = null,
    ) {}
}
