<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Rendering;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpMode;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpShape;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpSize;

/**
 * The fully evaluated, render-ready state of an
 * {@see OtpInput}.
 *
 * Every lazy value has been resolved by the time this object exists, so both
 * the Blade view and the Alpine layer read a static, pre-computed
 * description. {@see alpineConfig()} is the single JSON payload handed to the
 * client component (the live value is entangled separately, in Blade), and
 * {@see groupedIndexes()} is the exact cell/separator layout the template
 * walks — no grouping math happens in Blade.
 */
readonly class OtpViewModel
{
    /**
     * @param  array<int, int>  $groups  Resolved group sizes, summing to $length.
     * @param  array<int, string>  $placeholders  Per-cell placeholder character.
     */
    public function __construct(
        public int $length,
        public OtpMode $mode,
        public bool $isPrivate,
        public string $maskCharacter,
        public bool $isDisabled,
        public bool $isReadOnly,
        public bool $isRequired,
        public bool $isAutofocused,
        public bool $hasAutocomplete,
        public bool $shouldAutoSubmit,
        public ?string $autoSubmitAction,
        public array $groups,
        public ?string $separator,
        public OtpSize $size,
        public OtpShape $shape,
        public ?string $cellWidth,
        public ?string $gap,
        public array $placeholders,
    ) {}

    /**
     * The client-side configuration, everything except the entangled live
     * value (which Blade injects, so it stays reactive).
     *
     * @return array<string, mixed>
     */
    public function alpineConfig(): array
    {
        return [
            'length' => $this->length,
            'mode' => $this->mode->value,
            'characterClass' => $this->mode->characterClass(),
            'isPrivate' => $this->isPrivate,
            'isDisabled' => $this->isDisabled,
            'isReadOnly' => $this->isReadOnly,
            'hasAutocomplete' => $this->hasAutocomplete,
            'shouldAutoSubmit' => $this->shouldAutoSubmit,
            'autoSubmitAction' => $this->autoSubmitAction,
        ];
    }

    /**
     * The render layout: an ordered list of groups, each a list of absolute
     * cell indexes. Between two consecutive groups the template draws the
     * {@see $separator}.
     *
     * @return array<int, array<int, int>>
     */
    public function groupedIndexes(): array
    {
        $groups = [];
        $index = 0;

        foreach ($this->groups as $size) {
            $group = [];

            for ($i = 0; $i < $size; $i++) {
                $group[] = $index++;
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public function isGrouped(): bool
    {
        return count($this->groups) > 1;
    }

    /**
     * The per-instance sizing overrides, as CSS custom properties. Empty when
     * the size preset's own dimensions should stand.
     */
    public function rootStyles(): string
    {
        return implode(';', array_filter([
            filled($this->cellWidth) ? "--fi-otp-cell-width: {$this->cellWidth}" : null,
            filled($this->gap) ? "--fi-otp-gap: {$this->gap}" : null,
        ]));
    }

    public function placeholderFor(int $index): string
    {
        return $this->placeholders[$index] ?? '';
    }
}
