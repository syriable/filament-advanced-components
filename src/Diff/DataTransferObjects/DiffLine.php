<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects;

use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;

/**
 * One rendered row of a diff: its type, its line number in the old and/or
 * new revision, and the line's content (without the trailing line break).
 */
final readonly class DiffLine
{
    public function __construct(
        public DiffLineType $type,
        /** Null for pure additions — the line does not exist in the old revision. */
        public ?int $oldLineNo,
        /** Null for pure deletions — the line does not exist in the new revision. */
        public ?int $newLineNo,
        public string $content,
    ) {}
}
