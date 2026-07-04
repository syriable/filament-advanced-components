<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;

/**
 * The decoration stage of the pipeline: light, closure-based hooks that wrap
 * the renderer's HTML without replacing the renderer.
 *
 * Use a decorator for a one-off tweak (a wrapper element, a data attribute);
 * bind a whole {@see RendersOptions}
 * implementation when the change is structural.
 */
trait HasOptionDecorators
{
    /**
     * @var array<Closure>
     */
    protected array $optionDecorators = [];

    /**
     * @var array<Closure>
     */
    protected array $selectedLabelDecorators = [];

    /**
     * Transform each dropdown option's HTML after it is rendered. Decorators
     * run in registration order and receive the current `$html` and the
     * resolved `$option` view model (plus the field's usual injections):
     *
     * ```php
     * ->decorateOptionUsing(fn (string $html): string => "<div class=\"px-1\">{$html}</div>")
     * ```
     */
    public function decorateOptionUsing(Closure $decorator): static
    {
        $this->optionDecorators[] = $decorator;

        return $this;
    }

    /**
     * Transform each selected value's HTML (control text and multi-select
     * chips) after it is rendered.
     */
    public function decorateSelectedLabelUsing(Closure $decorator): static
    {
        $this->selectedLabelDecorators[] = $decorator;

        return $this;
    }

    protected function applyOptionDecorators(string $html, OptionViewModel $option): string
    {
        return $this->applyDecorators($this->optionDecorators, $html, $option);
    }

    protected function applySelectedLabelDecorators(string $html, OptionViewModel $option): string
    {
        return $this->applyDecorators($this->selectedLabelDecorators, $html, $option);
    }

    /**
     * @param  array<Closure>  $decorators
     */
    protected function applyDecorators(array $decorators, string $html, OptionViewModel $option): string
    {
        foreach ($decorators as $decorator) {
            $html = (string) $this->evaluate($decorator, [
                'html' => $html,
                'option' => $option,
            ]);
        }

        return $html;
    }
}
