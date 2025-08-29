<?php

namespace Wotz\FilamentLivePreview\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Pboivin\FilamentPeek\CachedPreview;

class LivePreviewScreen extends Component
{
    public string $token;
    public string $view;
    public array $data = [];

    public function mount()
    {
        abort_unless($this->token = request()->query('token'), 404);

        $this->refreshPreview();
    }

    #[Layout('layouts.app', [
        'titleForLayout' => 'Live Preview',
        'hasBgPattern' => false,
        'bgColor' => 'bg-gradient-to-light',
    ])]
    public function render()
    {
        return view($this->view)
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

        $this->view = $preview->view;
        $this->data = $preview->data;

        \Illuminate\Support\Facades\Log::info('Live preview refreshed', [$this->view, $this->data]);
    }
}
