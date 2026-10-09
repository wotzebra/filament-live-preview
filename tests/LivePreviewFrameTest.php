<?php

use Wotz\FilamentLivePreview\CachedPreview;
use Wotz\FilamentLivePreview\Livewire\LivePreviewScreen;

beforeEach(function () {
    view()->addLocation(__DIR__ . '/views');
});

it('renders a cached preview in the frame', function () {
    CachedPreview::make('EditPage', 'preview', ['title' => 'Not saved yet'])->put('preview-token');

    $this->get(route('live-preview-frame', ['token' => 'preview-token']))
        ->assertSuccessful()
        ->assertSee('Not saved yet');
});

it('refuses a token it has no preview for', function () {
    $this->get(route('live-preview-frame', ['token' => 'unknown']))
        ->assertNotFound();
});

it('resolves the screen by its namespaced name', function () {
    // Livewire 4 resolves a `::` name through its namespaces alone.
    expect(app('livewire.finder')->resolveClassComponentClassName('filament-live-preview::live-preview-screen'))
        ->toBe(LivePreviewScreen::class);
});
