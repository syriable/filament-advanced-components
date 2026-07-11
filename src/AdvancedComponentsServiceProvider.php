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
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Rendering\OptionRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\RendersBadges;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\LinkGenerator;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\TextMasker;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation\ConfirmationManager;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction;
use Syriable\Filament\Plugins\AdvancedComponents\Commands\AdvancedComponentsCommand;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Contracts\BuildsRollbackAction;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Rollback\RollbackManager;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\FormatsPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\NormalizesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\PhoneMetadataProvider;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\ValidatesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Formatting\PhoneNumberFormatter;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Metadata\LibPhoneNumberProvider;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Normalization\PhoneNumberNormalizer;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Validation\PhoneNumberValidator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Rendering\SeparatorRenderer;
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
        // these in a service provider to customize masking, link
        // generation, or badge rendering globally.
        $this->app->singleton(MasksText::class, TextMasker::class);
        $this->app->singleton(GeneratesLinks::class, LinkGenerator::class);
        $this->app->singleton(RendersBadges::class, BadgeRenderer::class);

        // The AdvancedSelect option renderer. Rebind this to customize the
        // per-option markup globally without subclassing the component.
        $this->app->singleton(RendersOptions::class, OptionRenderer::class);

        // The Separator renderer. Rebind this to change how every separator
        // in the app renders, without subclassing the component.
        $this->app->singleton(RendersSeparator::class, SeparatorRenderer::class);

        // The AdvancedToggle confirmation action builder. Rebind this to
        // change how every confirmation modal is constructed globally,
        // without subclassing the component.
        $this->app->singleton(BuildsConfirmationAction::class, ConfirmationManager::class);

        // The DiffField Rollback button builder. Rebind this to change how
        // every Rollback button looks or behaves globally, without
        // subclassing the component.
        $this->app->singleton(BuildsRollbackAction::class, RollbackManager::class);

        // The PhoneInput metadata/formatting/validation stack, all backed by
        // libphonenumber and resolved offline. Each layer is bound to its
        // contract, so an application can rebind any single one — a leaner
        // country dataset, a house formatting style, a stricter validator —
        // without touching the field. The formatter and normalizer are wired
        // to whichever provider/formatter won, so overriding upstream flows
        // through automatically.
        $this->app->singleton(PhoneMetadataProvider::class, LibPhoneNumberProvider::class);
        $this->app->singleton(FormatsPhoneNumbers::class, PhoneNumberFormatter::class);
        $this->app->singleton(NormalizesPhoneNumbers::class, PhoneNumberNormalizer::class);
        $this->app->singleton(ValidatesPhoneNumbers::class, PhoneNumberValidator::class);
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
            AlpineComponent::make('otp-input', __DIR__ . '/../resources/dist/components/otp-input.js'),
            AlpineComponent::make('phone-input', __DIR__ . '/../resources/dist/components/phone-input.js'),
            Css::make('advanced-select', __DIR__ . '/../resources/css/advanced-select.css'),
            Css::make('diff-field', __DIR__ . '/../resources/css/diff-field.css'),
            Css::make('advanced-toggle', __DIR__ . '/../resources/css/advanced-toggle.css'),
            Css::make('advanced-text', __DIR__ . '/../resources/css/advanced-text.css'),
            Css::make('multi-progress', __DIR__ . '/../resources/css/multi-progress.css'),
            Css::make('package-comparison', __DIR__ . '/../resources/css/package-comparison.css'),
            Css::make('separator', __DIR__ . '/../resources/css/separator.css'),
            Css::make('otp-input', __DIR__ . '/../resources/css/otp-input.css'),
            Css::make('phone-input', __DIR__ . '/../resources/css/phone-input.css'),
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
