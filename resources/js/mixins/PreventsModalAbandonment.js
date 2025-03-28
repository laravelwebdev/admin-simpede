import { mapGetters, mapMutations } from 'vuex'

export default {
  props: {
    show: { type: Boolean, default: false },
  },

  methods: {
    ...mapMutations(['allowLeavingModal', 'preventLeavingModal']),

    /**
     * Prevent accidental abandonment only if form was changed.
     */
    updateModalStatus() {
      this.preventLeavingModal()
    },

    /**
     * @param {Function} proceed
     * @param {Function} revert
     */
    handlePreventModalAbandonment(proceed, revert) {
      if (this.canLeaveModal) {
        proceed()
        return
      }

      if (
        window.confirm(
          this.__('Do you really want to leave? You have unsaved changes.')
        )
      ) {
        this.allowLeavingModal()
        proceed()
        return
      }

      revert()
    },
  },

  computed: {
    ...mapGetters(['canLeaveModal']),
  },
}
