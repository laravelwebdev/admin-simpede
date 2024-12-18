export default {
  data: () => ({
    pollingListener: null,
    currentlyPolling: false,
  }),

  /**
   * Unbind the polling listener before the component is destroyed.
   */
  beforeUnmount() {
    this.stopPolling()
  },

  methods: {
    initializePolling() {
      this.currentlyPolling =
        this.currentlyPolling || this.resourceResponse.polling

      if (this.currentlyPolling && this.pollingListener === null) {
        return this.startPolling()
      }
    },

    /**
     * Toggle polling for new resources.
     */
    togglePolling() {
      if (this.currentlyPolling) {
        this.stopPolling()
      } else {
        this.startPolling()
      }
    },

    /**
     * Pause polling for new resources.
     */
    stopPolling() {
      if (this.pollingListener) {
        clearInterval(this.pollingListener)
        this.pollingListener = null
      }

      this.currentlyPolling = false
    },

    /**
     * Start polling for new resources.
     */
    startPolling() {
      this.pollingListener = setInterval(() => {
        let selectedResources = this.selectedResources ?? []

        if (
          document.hasFocus() &&
          document.querySelectorAll('[data-modal-open]').length < 1 &&
          selectedResources.length < 1
        ) {
          this.getResources()
        }
      }, this.pollingInterval)

      this.currentlyPolling = true
    },

    /**
     * Restart polling for the resource.
     */
    restartPolling() {
      if (this.currentlyPolling === true) {
        this.stopPolling()
        this.startPolling()
      }
    },
  },

  computed: {
    /**
     * @returns {boolean}
     */
    initiallyPolling() {
      return this.resourceResponse.polling
    },

    /**
     * @returns {number}
     */
    pollingInterval() {
      return this.resourceResponse.pollingInterval
    },

    /**
     * Determine if the polling toggle button should be shown.
     *
     * @returns {boolean}
     */
    shouldShowPollingToggle() {
      if (!this.resourceResponse) return false

      return this.resourceResponse.showPollingToggle || false
    },
  },
}
