<?php

namespace Syriable\Filament\Plugins\AdvancedComponents;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\LinkGenerator;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\TextMasker;
use Syriable\Filament\Plugins\AdvancedComponents\Commands\AdvancedComponentsCommand;
use Syriable\Filament\Plugins\AdvancedComponents\Testing\TestsAdvancedComponents;

class AdvancedComponentsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-advanced-components';

    public static string $viewNamespace = 'filament-advanced-components';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('syriable/filament-advanced-components');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        // Contract bindings for the AdvancedTextColumn services. Rebind
        // these in a service provider to customize masking or link
        // generation globally.
        $this->app->singleton(MasksText::class, TextMasker::class);
        $this->app->singleton(GeneratesLinks::class, LinkGenerator::class);
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-advanced-components/{$file->getFilename()}"),
                ], 'filament-advanced-components-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsAdvancedComponents);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'syriable/filament-advanced-components';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('package-comparison', __DIR__ . '/../resources/dist/components/package-comparison.js'),
            Css::make('advanced-text', __DIR__ . '/../resources/css/advanced-text.css'),
            Css::make('multi-progress', __DIR__ . '/../resources/css/multi-progress.css'),
            Css::make('package-comparison', __DIR__ . '/../resources/css/package-comparison.css'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            AdvancedComponentsCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_filament-advanced-components_table',
        ];
    }
}
