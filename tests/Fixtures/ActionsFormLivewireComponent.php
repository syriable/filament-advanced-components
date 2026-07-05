<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Livewire\Component as LivewireComponent;

/**
 * A minimal Livewire component that owns a real, named ("form") schema and
 * can mount actions registered on its own fields — the exact combination
 * `AdvancedToggle::requiresConfirmation()` (and, natively, `Select`'s
 * `createOptionAction()` or `Repeater`'s item actions) depends on.
 *
 * The field(s) under test are supplied via the static `$components` property
 * rather than a constructor argument, since Livewire re-instantiates this
 * class on every request/action-call within a test and its public
 * properties must stay wire-serializable — schema components are not.
 */
class ActionsFormLivewireComponent extends LivewireComponent implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * @var Component | array<Component> | null
     */
    public static Component | array | null $components = null;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function mount(array $data = []): void
    {
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(Arr::wrap(static::$components))
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->form }}

                <x-filament-actions::modals />
            </div>
            BLADE;
    }
}
