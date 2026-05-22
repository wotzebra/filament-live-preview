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

let livePreviewWindow = null
let livePreviewSidebarOpen = false
let newTabWatcher = null

function hasNewTabConsumer() {
  return livePreviewWindow && !livePreviewWindow.closed
}

function hasAnyPreviewConsumer() {
  return livePreviewSidebarOpen || hasNewTabConsumer()
}

function startWatchingNewTab() {
  if (newTabWatcher) return

  newTabWatcher = setInterval(() => {
    if (!hasNewTabConsumer()) {
      clearInterval(newTabWatcher)
      newTabWatcher = null
    }
  }, 1000)
}

// Sidebar open/close is signalled by the existing browser events the
// PeekPreviewModal Alpine component already dispatches.
window.addEventListener('open-preview', () => {
  livePreviewSidebarOpen = true
})
window.addEventListener('close-preview', () => {
  livePreviewSidebarOpen = false
})

// Bind form-input listeners as soon as a [data-live-preview-form] exists.
// We don't gate this on `peek:modal-initialized` because the new-tab flow
// may run without the sidebar modal ever being rendered. Inside the
// debounced callback we bail out when no preview is actually being
// watched — otherwise every keystroke would fire a Livewire round-trip
// even when nobody is looking, making the form feel sluggish.
const refreshPreview = debounce(() => {
  if (!hasAnyPreviewConsumer()) return

  Livewire.dispatch('refreshPreview')
}, 500)

function bindLivePreviewFormListeners() {
  const livePreviewForm = document.querySelector('[data-live-preview-form]')
  if (!livePreviewForm) return
  if (livePreviewForm.dataset.livePreviewBound === 'true') return

  livePreviewForm.dataset.livePreviewBound = 'true'

  // Native events cover regular field edits.
  livePreviewForm.addEventListener('change', refreshPreview)
  livePreviewForm.addEventListener('input', refreshPreview)
  window.addEventListener('submit', refreshPreview)
}

document.addEventListener('DOMContentLoaded', bindLivePreviewFormListeners)
document.addEventListener('livewire:init', bindLivePreviewFormListeners)
document.addEventListener('livewire:navigated', bindLivePreviewFormListeners)

// Inputs that commit their value through a Livewire modal (attachment
// picker, architect editor, etc.) never fire native input/change events on
// the form root — the user clicks a button inside the modal and the new
// value is pushed back via a Livewire request. We listen to Livewire's
// `commit` hook so any successful server round-trip retriggers the preview.
//
// To avoid an infinite loop with our own refresh round-trip we scan the
// whole commit payload for the string `refreshPreview`; Livewire's commit
// shape changes across versions and putting the marker anywhere in it is
// enough to identify the response to our own dispatch.
document.addEventListener('livewire:init', () => {
  if (typeof Livewire?.hook !== 'function') return

  Livewire.hook('commit', ({ component, commit, succeed }) => {
    succeed(() => {
      const componentName = component?.name ?? ''
      if (componentName.includes('live-preview')) return

      let payload = ''
      try {
        payload = JSON.stringify(commit ?? {})
      } catch (e) {
        payload = ''
      }
      if (payload.includes('refreshPreview')) return

      refreshPreview()
    })
  })
})

// Browsers only honour window.open when called synchronously from a real
// user gesture. The Filament action does a Livewire round-trip before the
// URL is known, so by the time the backend dispatches `open-preview-new-tab`
// the gesture has expired and popup blockers drop the call. To work around
// that, we intercept the click in the capture phase and pre-open a blank
// tab while the gesture is still alive, then redirect it once the URL
// arrives from the server.
document.addEventListener(
  'click',
  (event) => {
    const trigger = event.target.closest('[data-live-preview-open-tab]')
    if (!trigger) return

    if (!hasNewTabConsumer()) {
      livePreviewWindow = window.open('about:blank', 'filament-live-preview')
    } else {
      livePreviewWindow.focus()
    }
  },
  true
)

window.addEventListener('open-preview-new-tab', (event) => {
  const url = event?.detail?.iframeUrl ?? event?.detail?.[0]?.iframeUrl
  if (!url) return

  if (hasNewTabConsumer()) {
    livePreviewWindow.location.href = url
    livePreviewWindow.focus()
  } else {
    // Fallback if no pre-opened tab is available (e.g. trigger missing
    // the data attribute). Likely to be blocked, but we still try.
    livePreviewWindow = window.open(url, 'filament-live-preview')
  }

  startWatchingNewTab()
})
