<?php

namespace Syriable\FilamentAdvancedComponents;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AdvancedComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-advanced-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make(
                id: 'advanced-components-styles',
                path: __DIR__.'/../resources/dist/advanced-components-styles.css'
            )->loadedOnRequest(),
        ], 'syriable/filament-advanced-components');


        Wizard::macro('hiddenHeader',fn() => $this->extraAttributes(['class' => 'wizard-hidden-header']));

        Textarea::macro('counter', fn () => $this->fieldWrapperView('filament-advanced-components::filament.components.textarea')->extraAlpineAttributes(['x-init' => '$watch(\'state\',value => length = value?.length); length = state?.length ?? length']));

        Toggle::macro(
            'confirmation',
            fn () => $this->live()
            ->afterStateUpdated(function ($component, $livewire) {
                $livewire->mountAction('notify_users_confirmation', [], [
                    'recordKey' => $livewire->getRecord()?->getKey(),
                    'schemaComponent' => $component->getId(),
                ]);
            })
                ->registerActions([
                    Action::make('notify_users_confirmation')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($component) => $component->getLabel())
                        ->modalAlignment('start')
                        ->modalCancelAction(false)
                        ->modalSubmitAction(false)
                        ->closeModalByClickingAway(false)
                        ->closeModalByEscaping(false)
                        ->modalIconColor('danger')
                        ->modalCloseButton(false)
                        ->extraModalFooterActions(fn (Action $action): array => [$action->makeModalSubmitAction('yes', arguments: ['submit' => true])->icon('heroicon-m-check')->color('success'), $action->makeModalSubmitAction('no', arguments: ['cancel' => true])->icon('heroicon-m-x-mark')->color('danger')])
                        ->action(function (array $arguments, Set $set, $component) {
                            if (data_get($arguments, 'cancel', false)) {
                                $set($component->getName(), ! $component->getState());
                            }
                        }),
                ]),
        );
    }
}
