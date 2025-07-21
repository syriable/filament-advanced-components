<?php

namespace Syriable\FilamentAdvancedComponents;

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
    }
}
