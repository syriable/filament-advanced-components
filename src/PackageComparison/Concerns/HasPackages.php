<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns;

use Closure;

/**
 * Configuration for the package axis (the columns) of a `PackageComparison`
 * field: how many packages are allowed, how many a fresh field starts with,
 * and what the user may do to them. Every option accepts a `Closure` and is
 * resolved through `evaluate()`, exactly like native Filament options.
 */
trait HasPackages
{
    protected int | Closure $minPackages = 1;

    protected int | Closure | null $maxPackages = null;

    protected int | Closure $defaultPackages = 3;

    protected bool | Closure $isPackageReorderingAllowed = true;

    protected bool | Closure $arePackagesAddable = true;

    protected bool | Closure $arePackagesDeletable = true;

    protected bool | Closure $arePackagesRenameable = true;

    public function minPackages(int | Closure $count): static
    {
        $this->minPackages = $count;

        return $this;
    }

    public function maxPackages(int | Closure | null $count): static
    {
        $this->maxPackages = $count;

        return $this;
    }

    /**
     * How many packages a fresh (empty-state) field is seeded with.
     */
    public function defaultPackages(int | Closure $count): static
    {
        $this->defaultPackages = $count;

        return $this;
    }

    public function allowPackageReordering(bool | Closure $condition = true): static
    {
        $this->isPackageReorderingAllowed = $condition;

        return $this;
    }

    public function addablePackages(bool | Closure $condition = true): static
    {
        $this->arePackagesAddable = $condition;

        return $this;
    }

    public function deletablePackages(bool | Closure $condition = true): static
    {
        $this->arePackagesDeletable = $condition;

        return $this;
    }

    public function renameablePackages(bool | Closure $condition = true): static
    {
        $this->arePackagesRenameable = $condition;

        return $this;
    }

    public function getMinPackages(): int
    {
        return max(0, (int) $this->evaluate($this->minPackages));
    }

    public function getMaxPackages(): ?int
    {
        $max = $this->evaluate($this->maxPackages);

        return ($max === null) ? null : max($this->getMinPackages(), (int) $max);
    }

    public function getDefaultPackages(): int
    {
        $default = max($this->getMinPackages(), (int) $this->evaluate($this->defaultPackages));

        $max = $this->getMaxPackages();

        return ($max === null) ? $default : min($default, $max);
    }

    public function isPackageReorderingAllowed(): bool
    {
        return (bool) $this->evaluate($this->isPackageReorderingAllowed) && (! $this->isDisabled());
    }

    public function arePackagesAddable(): bool
    {
        return (bool) $this->evaluate($this->arePackagesAddable) && (! $this->isDisabled());
    }

    public function arePackagesDeletable(): bool
    {
        return (bool) $this->evaluate($this->arePackagesDeletable) && (! $this->isDisabled());
    }

    public function arePackagesRenameable(): bool
    {
        return (bool) $this->evaluate($this->arePackagesRenameable) && (! $this->isDisabled());
    }
}
