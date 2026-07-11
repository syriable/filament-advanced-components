<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects;

use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\WordDiffGenerator;

/**
 * One run of consecutive same-kind words (or whitespace) in a word-level
 * diff: unchanged, added, or removed. Runs are pre-merged by
 * {@see WordDiffGenerator} so a contiguous change renders as a single styled
 * span rather than one per word.
 */
final readonly class WordDiffToken
{
    public function __construct(
        public DiffLineType $type,
        public string $text,
    ) {}
}
