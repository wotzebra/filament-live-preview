<?php

namespace Wotz\FilamentLivePreview;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Wotz\FilamentLivePreview\Livewire\LivePreviewScreen;

class LivePreviewPlugin implements Plugin
{
    const PACKAGE = 'wotz/filament-live-preview';

    const ID = 'filament-live-preview';

    protected bool $shouldLoadPluginScripts = true;

    public function disablePluginScripts(): self
    {
        $this->shouldLoadPluginScripts = false;

        return $this;
    }

    public function shouldLoadPluginScripts(): bool
    {
        return $this->shouldLoadPluginScripts;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        Livewire::component(
            'filament-live-preview::live-preview-screen',
            LivePreviewScreen::class
        );

        $panel->renderHook(
            'panels::body.end',
            fn () => view('filament-peek::preview-modal'),
        );

        if ($this->shouldLoadPluginScripts()) {
            FilamentAsset::register([
                Js::make(static::ID, __DIR__ . '/../resources/dist/filament-live-preview.js'),
            ], package: static::PACKAGE);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
