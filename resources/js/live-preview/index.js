import { debounce } from 'lodash-es'
import { dispatch } from 'alpinejs/src/utils/dispatch'

const editorFocusOutHandlers = []

document.addEventListener('alpine:init', () => {
  dispatch(document, 'peek:initializing')

  Alpine.data('PeekPreviewModal', (config) => ({
    config,
    isOpen: false,
    canRotatePreset: false,
    activeDevicePreset: null,
    iframeUrl: null,
    iframeStyle: {
      width: '100%',
      height: '100%',
    },

    init() {
      dispatch(document, 'peek:modal-initializing', { modal: this })

      const debounceTime = this.config.editorAutoRefreshDebounceTime || 1000

      this.refreshPreview = debounce(() => Livewire.dispatch('refreshPreview'), debounceTime)

      this.setDevicePreset()

      setTimeout(() => dispatch(document, 'peek:modal-initialized', { modal: this }), 0)

      Livewire.dispatch('openPreview')
    },

    setIframeDimensions(width, height) {
      this.iframeStyle.width = width
      this.iframeStyle.height = height
    },

    setDevicePreset(name) {
      name = name || this.config.initialDevicePreset

      if (!this.config.devicePresets?.[name]) return
      if (!this.config.devicePresets[name].width) return
      if (!this.config.devicePresets[name].height) return

      this.setIframeDimensions(
        this.config.devicePresets[name].width,
        this.config.devicePresets[name].height
      )

      this.canRotatePreset = this.config.devicePresets[name].canRotatePreset || false

      this.activeDevicePreset = name
    },

    isActiveDevicePreset(name) {
      return this.activeDevicePreset === name
    },

    rotateDevicePreset() {
      const newWidth = this.iframeStyle.height
      const newHeight = this.iframeStyle.width

      this.setIframeDimensions(newWidth, newHeight)
    },

    onOpenPreview($event) {
      dispatch(document, 'peek:modal-opening', { modal: this })

      document.body.classList.add('is-filament-peek-preview-modal-open')

      if (this.config.shouldRestoreIframePositionOnRefresh) {
        this._restoreIframeScrollPosition()
      }

      this.iframeUrl = $event.detail.iframeUrl
      this.isOpen = true

      setTimeout(() => dispatch(document, 'peek:modal-opened', { modal: this }), 0)
    },

    _restoreIframeScrollPosition() {
      try {
        const iframe = this.$refs.previewBody.querySelector('iframe')

        if (iframe && iframe.contentWindow) {
          this._iframeScrollPosition = iframe.contentWindow.scrollY
          iframe.onload = () => {
            iframe?.contentWindow?.scrollTo(0, this._iframeScrollPosition || 0)
          }
        }
      } catch (e) {
        // pass
      }
    },

    onClosePreview($event) {
      setTimeout(() => this._closeModal(), $event?.detail?.delay ? 250 : 0)
    },

    _closeModal() {
      dispatch(document, 'peek:modal-closing', { modal: this })

      document.body.classList.remove('is-filament-peek-preview-modal-open')

      this.iframeUrl = null
      this.isOpen = false

      setTimeout(() => dispatch(document, 'peek:modal-closed', { modal: this }), 0)
    },

    onEditorFocusOut($event) {
      for (const handler of editorFocusOutHandlers) {
        if (typeof handler === 'function') {
          handler($event.detail, this)
        }
      }
    }
  }))

  dispatch(document, 'peek:initialized')
})

document.addEventListener('peek:modal-initialized', (event) => {
  const $modal = event.detail.modal
  const livePreviewForm = document.querySelector('[data-live-preview-form]')

  if (livePreviewForm) {
    const refreshPreviewEvent = () => $modal.refreshPreview()

    window.addEventListener('input', refreshPreviewEvent)
    window.addEventListener('change', refreshPreviewEvent)
    window.addEventListener('submit', refreshPreviewEvent)
    window.addEventListener('pointerout', refreshPreviewEvent)
  }
})
