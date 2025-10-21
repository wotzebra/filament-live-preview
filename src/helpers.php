<?php

if (! function_exists('is_live_preview_request')) {
    function is_live_preview_request(): bool
    {
        if (request()->routeIs('live-preview-frame')) {
            return true;
        }

        if (app('livewire')->isLivewireRequest()) {
            $snapshot = json_decode(request('components.0.snapshot'), true);

            if (($snapshot['memo']['name'] ?? '') === 'filament-live-preview::live-preview-screen') {
                return true;
            }
        }

        return false;
    }
}
