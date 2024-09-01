<template>
  <Modal :show="show" size="sm">
    <form
      @submit.prevent="handleConfirm"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
      style="width: 460px"
    >
      <slot>
        <ModalHeader v-text="__('Restore Resource')" />
        <ModalContent>
          <p class="leading-normal">
            {{ __('Are you sure you want to restore the selected resources?') }}
          </p>
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <LinkButton
            type="button"
            dusk="cancel-restore-button"
            @click.prevent="handleClose"
            class="mr-3"
          >
            {{ __('Cancel') }}
          </LinkButton>

          <Button
            type="submit"
            ref="confirmButton"
            dusk="confirm-restore-button"
            :loading="working"
          >
            {{ __('Restore') }}
          </Button>
        </div>
      </ModalFooter>
    </form>
  </Modal>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['confirm', 'close'],

  props: {
    show: { type: Boolean, default: false },
  },

  data: () => ({
    working: false,
  }),

  watch: {
    show(showing) {
      if (showing === false) {
        this.working = false
      }
    },
  },

  methods: {
    handleClose() {
      this.$emit('close')
      this.working = false
    },

    handleConfirm() {
      this.$emit('confirm')
      this.working = true
    },
  },
}
</script>
