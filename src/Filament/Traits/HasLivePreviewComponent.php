<?php

namespace Wotz\FilamentLivePreview\Filament\Traits;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Pboivin\FilamentPeek\Facades\Peek;
use Pboivin\FilamentPeek\Support\Cache;
use Wotz\FilamentLivePreview\CachedPreview;
use Wotz\FilamentLivePreview\Events\RefreshLivePreview;
use Wotz\LocaleCollection\Facades\LocaleCollection;

trait HasLivePreviewComponent
{
    protected array $initialPreviewModalData = [];

    protected array $previewModalData = [];

    protected ?Model $previewableRecord = null;

    protected bool $shouldCallHooksBeforePreview = false;

    protected bool $shouldDehydrateBeforePreview = true;

    public string $token = '';

    public bool $isPreviewing = false;

    protected function getPreviewModalUrl(): ?string
    {
        return null;
    }

    protected function getPreviewModalView(): ?string
    {
        return null;
    }

    protected function getPreviewModalDataRecordKey(): string
    {
        return 'record';
    }

    protected function mutatePreviewModalData(array $data): array
    {
        return $data;
    }

    protected function getShouldCallHooksBeforePreview(): bool
    {
        return $this->shouldCallHooksBeforePreview;
    }

    protected function getShouldDehydrateBeforePreview(): bool
    {
        return $this->shouldDehydrateBeforePreview;
    }

    /** @internal */
    public static function renderPreviewModalView(?string $view, array $data): string
    {
        return Peek::html()->injectPreviewModalStyle(
            view($view, $data)->render()
        );
    }

    /** @internal */
    protected function preparePreviewModalData(): array
    {
        $shouldCallHooks = $this->getShouldCallHooksBeforePreview();
        $shouldDehydrate = $this->getShouldDehydrateBeforePreview();
        $record = null;

        if ($this->previewableRecord) {
            $record = $this->previewableRecord;
        } elseif (method_exists($this, 'mutateFormDataBeforeCreate')) {
            if (! $shouldCallHooks && $shouldDehydrate) {
                $this->form->validate();
                $this->form->callBeforeStateDehydrated();
            }
            $data = $this->mutateFormDataBeforeCreate($this->form->getState($shouldCallHooks));
            $record = $this->getModel()::make($data);
        } elseif (method_exists($this, 'mutateFormDataBeforeSave')) {
            if (! $shouldCallHooks && $shouldDehydrate) {
                $this->form->validate();
                $this->form->callBeforeStateDehydrated();
            }
            $data = $this->mutateFormDataBeforeSave($this->form->getState($shouldCallHooks));
            $record = $this->getRecord();
            $record->fill($data);
        } elseif (method_exists($this, 'getRecord')) {
            $record = $this->getRecord();
        }

        Log::info($this->token, $this->data);

        return array_merge(
            $this->initialPreviewModalData,
            [
                $this->getPreviewModalDataRecordKey() => $record,
            ]
        );
    }

    #[On('openPreview')]
    public function openPreview(): void
    {
        try {
            $previewModalUrl = $this->buildPreviewUrl();
        } catch (Halt $exception) {
            $this->closePreview();

            return;
        }

        $this->dispatch(
            'open-preview',
            iframeUrl: $previewModalUrl,
        );
    }

    public function openPreviewInNewTab(): void
    {
        try {
            $previewUrl = $this->buildPreviewUrl();
        } catch (Halt $exception) {
            return;
        }

        $this->dispatch(
            'open-preview-new-tab',
            iframeUrl: $previewUrl,
        );
    }

    /** @internal */
    protected function buildPreviewUrl(): string
    {
        $this->previewModalData = $this->mutatePreviewModalData($this->preparePreviewModalData());

        if ($previewModalUrl = $this->getPreviewModalUrl()) {
            return $previewModalUrl;
        }

        if (! ($view = $this->getPreviewModalView()) || ! config('filament-peek.internalPreviewUrl.enabled', false)) {
            throw new InvalidArgumentException('Missing preview modal URL or Blade view.');
        }

        $this->token = app(Cache::class)->createPreviewToken();

        $request = Request::create(request()->header('referer'));

        $locale = $request->query('locale');

        if (! LocaleCollection::firstLocale($locale)) {
            $locale = LocaleCollection::first()->locale();
        }

        CachedPreview::make(static::class, $view, $this->previewModalData, $locale)
            ->put($this->token, config('filament-peek.internalPreviewUrl.cacheDuration', 60));

        return route('live-preview-frame', [
            'token' => $this->token,
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @internal */
    public function closePreview(): void
    {
        $this->dispatch('close-preview');
    }

    /** @internal */
    public function setPreviewableRecord(Model $record): void
    {
        $this->previewableRecord = $record;
    }

    /** @internal */
    public function initialPreviewModalData(array $data): void
    {
        $this->initialPreviewModalData = $data;
    }

    public function isPreviewing(): bool
    {
        return $this->isPreviewing;
    }

    public function toggleIsPreviewing(): void
    {
        $this->isPreviewing = match ($this->isPreviewing()) {
            true => false,
            false => true,
        };

        if ($this->isPreviewing) {
            $this->openPreview();
        } else {
            $this->closePreview();
        }
    }

    public function getLivePreviewAction(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('preview')
                ->label(__('filament-live-preview::action.open in sidebar'))
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(fn () => $this->toggleIsPreviewing()),

            Action::make('previewInTab')
                ->label(__('filament-live-preview::action.open in new tab'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->extraAttributes(['data-live-preview-open-tab' => true])
                ->action(fn () => $this->openPreviewInNewTab()),
        ])
            ->label(__('filament-live-preview::action.preview'))
            ->icon('heroicon-m-ellipsis-vertical')
            // Secondary: previewing is never the primary action on an edit page — saving
            // is. Chain ->color('primary') on the returned group to opt back in.
            ->color('gray')
            ->button();
    }

    #[On('refreshPreview')]
    public function refreshPreview(): void
    {
        try {
            $this->previewModalData = $this->mutatePreviewModalData($this->preparePreviewModalData());

            if (($view = $this->getPreviewModalView()) && config('filament-peek.internalPreviewUrl.enabled', false)) {
                $request = Request::create(request()->header('referer'));

                $locale = $request->query('locale');

                CachedPreview::make(static::class, $view, $this->previewModalData, $locale)
                    ->put($this->token, config('filament-peek.internalPreviewUrl.cacheDuration', 60));
            } else {
                throw new InvalidArgumentException('Missing preview modal URL or Blade view.');
            }

            RefreshLivePreview::dispatch();
        } catch (Halt $exception) {
        }
    }

    #[On('callMountedAction')]
    public function refreshPreview2(): void
    {
        $this->refreshPreview();
    }
}
