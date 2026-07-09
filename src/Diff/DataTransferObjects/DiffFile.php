<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects;

/**
 * The complete computed diff for one pair of old/new strings: the ordered
 * hunks to render plus the header stats (filename, additions, deletions).
 */
final readonly class DiffFile
{
    /**
     * @param  array<int, DiffHunk>  $hunks
     */
    public function __construct(
        public ?string $filename,
        public array $hunks,
        public int $additionsCount,
        public int $deletionsCount,
    ) {}

    /**
     * The GitHub-style header stat squares: with fewer changes than squares,
     * one colored square per change and the rest neutral; with more, all
     * squares split proportionally (a non-zero side always keeps at least
     * one square).
     *
     * @return list<'addition'|'deletion'|'neutral'>
     */
    public function statSquares(int $total = 5): array
    {
        $changes = $this->additionsCount + $this->deletionsCount;

        if ($changes === 0) {
            return array_fill(0, $total, 'neutral');
        }

        if ($changes <= $total) {
            $additionSquares = $this->additionsCount;
            $deletionSquares = $this->deletionsCount;
        } else {
            $additionSquares = (int) round($total * $this->additionsCount / $changes);

            if ($this->additionsCount > 0 && $additionSquares === 0) {
                $additionSquares = 1;
            }

            if ($this->deletionsCount > 0 && $additionSquares === $total) {
                $additionSquares = $total - 1;
            }

            $deletionSquares = $total - $additionSquares;
        }

        return [
            ...array_fill(0, $additionSquares, 'addition'),
            ...array_fill(0, $deletionSquares, 'deletion'),
            ...array_fill(0, $total - $additionSquares - $deletionSquares, 'neutral'),
        ];
    }
}
