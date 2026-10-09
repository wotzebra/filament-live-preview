<?php

namespace Wotz\FilamentLivePreview\Providers;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLivePreviewServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-live-preview')
            ->setBasePath(__DIR__ . '/../')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasRoute('web')
            ->hasViews();
    }

    public function packageBooted(): void
    {
        /*
         * Livewire 4 resolves a `::` name through its namespace only, never through
         * Livewire::component(), so the screen needs one. Registered here rather than
         * in the panel plugin: the preview frame route lives outside the panel.
         */
        Livewire::addNamespace(
            'filament-live-preview',
            classNamespace: 'Wotz\\FilamentLivePreview\\Livewire',
        );
    }
}
