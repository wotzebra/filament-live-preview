<?php

namespace Wotz\FilamentLivePreview\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Wotz\FilamentLivePreview\CachedPreview;
use Wotz\LocaleCollection\Facades\LocaleCollection;

class LivePreviewScreen extends Component
{
    public string $token;
    public string $view;
    public array $data = [];

    public function mount()
    {
        $this->token = request()->query('token');

        abort_unless(filled($this->token), 404);

        $this->refreshPreview();
    }

    public function render()
    {
        return view($this->view)
            ->layout('layouts.app', $this->data)
            ->with($this->data);
    }

    #[On('echo:live-preview,.refresh-live-preview')]
    public function listenForMessage()
    {
        $this->refreshPreview();
    }

    private function refreshPreview()
    {
        abort_unless((bool) $preview = CachedPreview::get($this->token), 404);

        if ($preview->locale) {
            app()->setLocale($preview->locale);
        }

        $this->view = $preview->view;
        $this->data = $preview->data;

        \Illuminate\Support\Facades\Log::info('Live preview refreshed', [$this->view, $this->data]);
    }
}
