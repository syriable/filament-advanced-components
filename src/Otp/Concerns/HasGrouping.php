<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * Visual grouping of the cells, e.g. `123 - 456`. Grouping is presentation
 * only — it never changes the stored value, which is always the plain,
 * separator-free code.
 *
 * `group(3)` splits the cells into runs of three. `group([2, 3, 2])` gives
 * explicit, uneven runs. Any leftover cells (when the length is not a
 * multiple of the run size) form a final, shorter group, so grouping and
 * {@see HasLength length} never need to be kept in sync by hand.
 */
trait HasGrouping
{
    /**
     * @var int | array<int, int> | Closure | null
     */
    protected int | array | Closure | null $group = null;

    /**
     * @param  int | array<int, int> | Closure | null  $size  A single run
     *                                                        length applied repeatedly, or an explicit list of run lengths.
     */
    public function group(int | array | Closure | null $size = 3): static
    {
        $this->group = $size;

        return $this;
    }

    /**
     * Whether `group()` has been called at all. Used to auto-pair grouping
     * and separators without overwriting an explicit configuration.
     */
    public function isGroupingConfigured(): bool
    {
        return $this->group !== null;
    }

    /**
     * Resolves the configuration into a concrete list of group sizes that
     * always sums to the field's length. An empty/absent configuration
     * yields a single group spanning every cell.
     *
     * @return array<int, int>
     */
    public function getGroups(): array
    {
        $length = $this->getLength();
        $group = $this->evaluate($this->group);

        if (blank($group)) {
            return [$length];
        }

        // An explicit list of run lengths: keep only positive integers, then
        // clamp the running total so the groups can never exceed the code.
        if (is_array($group)) {
            $groups = [];
            $remaining = $length;

            foreach ($group as $size) {
                $size = (int) $size;

                if ($size <= 0 || $remaining <= 0) {
                    continue;
                }

                $take = min($size, $remaining);
                $groups[] = $take;
                $remaining -= $take;
            }

            // Any cells not covered by the list join the final group so no
            // cell is ever orphaned.
            if ($remaining > 0) {
                $groups[] = $remaining;
            }

            return $groups !== [] ? $groups : [$length];
        }

        // A single repeated run length.
        $size = max(1, (int) $group);

        if ($size >= $length) {
            return [$length];
        }

        $groups = [];
        $remaining = $length;

        while ($remaining > 0) {
            $take = min($size, $remaining);
            $groups[] = $take;
            $remaining -= $take;
        }

        return $groups;
    }

    public function isGrouped(): bool
    {
        return count($this->getGroups()) > 1;
    }
}
