<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts;

use Filament\Support\Components\ViewComponent;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;

/**
 * The rendering stage of the pipeline: turns a resolved {@see OptionViewModel}
 * into the HTML string that Filament's select shows.
 *
 * Two surfaces are rendered independently so the dropdown can be rich while
 * the selected value stays compact:
 *
 *  - {@see renderOption()} — a row in the open dropdown (icon, label,
 *    description, badge).
 *  - {@see renderSelectedLabel()} — the chosen value shown in the control and
 *    in multi-select chips (icon, label, badge — no description).
 *
 * Bind a different implementation in a service provider to change the markup
 * globally without touching the component:
 *
 * ```php
 * $this->app->bind(RendersOptions::class, MyOptionRenderer::class);
 * ```
 */
interface RendersOptions
{
    /**
     * Render one option as it appears in the open dropdown list.
     */
    public function renderOption(OptionViewModel $option, ViewComponent $component): string;

    /**
     * Render one option as the selected value (control text or chip).
     */
    public function renderSelectedLabel(OptionViewModel $option, ViewComponent $component): string;
}
