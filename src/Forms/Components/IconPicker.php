<?php

namespace Syriable\FilamentAdvancedComponents\Forms\Components;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Icon;
use Filament\Support\Icons\Heroicon;

class IconPicker extends Select
{
    protected string $view = 'filament-advanced-components::forms.components.icon-picker';

    public function setUp(): void
    {
        parent::setUp();
        $icons = $this->getIcons();

        $this->allowHtml();
        $this->options($icons);
        $this->optionsLimit(count($icons));
        $this->searchable();
        $this->getSearchResultsUsing(fn ($search) => collect($icons)
            ->filter(fn ($icon, $key) => str_contains($key, $search))
            ->toArray());

        $this->native(false);
        // $this->dehydrateStateUsing(fn ($state) => dd($state));

    }

    /**
     * Get the available icons.
     */
    public static function getIcons(): array
    {
        $icons = [];
        foreach (Heroicon::cases() as $heroicon) {
            $icons[$heroicon->value] = Icon::make($heroicon)->toHtml();
        }

        // dd($icons);
        return $icons;
    }
}
