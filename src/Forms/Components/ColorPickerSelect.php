<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\CanStoreAsKey;
use Syriable\FilamentAdvancedComponents\Forms\Components\Concerns\HasColors;
use Closure;
use Exception;
use Filament\Forms\Components\Select;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Js;
use JsonException;

class ColorPickerSelect extends Select
{
    use CanStoreAsKey;
    use HasColors;

    protected bool | Closure $isHtmlAllowed = true;

    protected bool | Closure $isNative = false;

    /**
     * @throws JsonException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $styles = Js::from(\Filament\Support\Facades\FilamentAsset::getStyleHref('advanced-components-styles', 'syriable/filament-advanced-components'));

        $this
            ->extraAttributes([
                'x-data' => '',
                'x-load-css' => '[' . $styles . ']',
            ])
            ->afterStateHydrated(function (ColorPickerSelect $component, string | array | null $state) {
                if (! $state) {
                    return;
                }

                if (is_array($state)) {
                    $component->state($state['key'] ?? null);

                    return;
                }

                $component->state($state);
            })
            ->dehydrateStateUsing(function (ColorPickerSelect $component, string | array | null $state) {
                if (! $state) {
                    return null;
                }

                if (is_string($state) && ! $this->shouldStoreAsKey()) {
                    return $component->getColors()[$state];
                }

                return $state;
            })
            ->options($this->getData());
    }

    public function getData(): array
    {
        return collect($this->getColors())->sortBy('label')->mapWithKeys(function (array $color) {
            return [$color['key'] => $this->getOptionView($color)];
        })->toArray();
    }

    public function getOptionView(array $color): string | Htmlable
    {
        return Blade::render('filament-advanced-components::forms.components.select-option', ['color' => $color]);
    }
}