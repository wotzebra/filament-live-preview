<?php

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Wotz\FilamentLivePreview\Livewire\LivePreviewScreen;

Route::get('live-preview-frame', LivePreviewScreen::class)
    // ->middleware(Authenticate::class)
    ->name('live-preview-frame');
