<div
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="filament-peek-modal-title"
    x-data="PeekPreviewModal({
        devicePresets: @js(config('filament-peek.devicePresets', false)),
        initialDevicePreset: @js(config('filament-peek.initialDevicePreset', 'fullscreen')),
        allowIframeOverflow: @js(config('filament-peek.allowIframeOverflow', false)),
        shouldCloseModalWithEscapeKey: @js(config('filament-peek.closeModalWithEscapeKey', true)),
        editorAutoRefreshDebounceTime: @js(config('filament-peek.builderEditor.autoRefreshDebounceMilliseconds', 500)),
        shouldRestoreIframePositionOnRefresh: @js(config('filament-peek.builderEditor.preservePreviewScrollPosition', false)),
        editorSidebarMinWidth: @js(config('filament-peek.builderEditor.sidebarMinWidth', '30rem')),
        editorSidebarInitialWidth: @js(config('filament-peek.builderEditor.sidebarInitialWidth', '30rem')),
    })"
    x-bind:class="{
        'filament-peek-modal': true
    }"
    x-on:open-preview.window="onOpenPreview($event)"
    x-on:refresh-preview.window="onRefreshPreview($event)"
    x-on:close-preview.window="onClosePreview($event)"
    x-on:focus-out.window="onEditorFocusOut($event)"
    x-cloak
>
    <div class="filament-peek-panel filament-peek-preview">
        <div class="filament-peek-panel-header">
            @if (config('filament-peek.devicePresets', false))
                <div class="filament-peek-device-presets">
                    @foreach (config('filament-peek.devicePresets') as $presetName => $presetConfig)
                        <button
                            type="button"
                            data-preset-name="{{ $presetName }}"
                            x-on:click="setDevicePreset('{{ $presetName }}')"
                            x-bind:class="{'is-active-device-preset': isActiveDevicePreset('{{ $presetName }}')}"
                        >
                            <x-filament::icon
                                :icon="$presetConfig['icon'] ?? 'heroicon-o-computer-desktop'"
                                :class="\Illuminate\Support\Arr::toCssClasses(['rotate-90' => $presetConfig['rotateIcon'] ?? false])"
                            />
                        </button>
                    @endforeach

                    <button
                        type="button"
                        class="filament-peek-rotate-preset"
                        x-on:click="rotateDevicePreset()"
                        x-bind:disabled="!canRotatePreset"
                    >
                        @include('filament-peek::partials.icon-rotate')
                    </button>
                </div>
            @endif
        </div>

        <div
            x-ref="previewBody"
            class="{{ \Illuminate\Support\Arr::toCssClasses([
                'filament-peek-panel-body' => true,
                'allow-iframe-overflow' => config('filament-peek.allowIframeOverflow', false),
            ]) }}"
        >
            <template x-if="iframeUrl">
                <iframe
                    x-bind:src="iframeUrl"
                    x-bind:style="iframeStyle"
                    frameborder="0"
                ></iframe>
            </template>

            <div class="filament-peek-iframe-cover"></div>
        </div>
    </div>
</div>

