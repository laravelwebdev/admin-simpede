<template>
  <Modal :show="show" role="alertdialog" size="sm">
    <form
      @submit.prevent="handleConfirm"
      class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
    >
      <slot>
        <ModalHeader v-text="__(`${uppercaseMode} Resource`)" />
        <ModalContent>
          <p class="leading-normal">
            {{
              __(
                'Are you sure you want to ' + mode + ' the selected resources?'
              )
            }}
          </p>
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <LinkButton
            type="button"
            dusk="cancel-delete-button"
            @click.prevent="handleClose"
            class="mr-3"
          >
            {{ __('Cancel') }}
          </LinkButton>

          <Button
            type="submit"
            ref="confirmButton"
            dusk="confirm-delete-button"
            :loading="working"
            state="danger"
            :label="__(uppercaseMode)"
          />
        </div>
      </ModalFooter>
    </form>
  </Modal>
</template>

<script>
import startCase from 'lodash/startCase'
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['confirm', 'close'],

  props: {
    show: { type: Boolean, default: false },

    mode: {
      type: String,
      default: 'delete',
      validator: function (value) {
        return ['force delete', 'delete', 'detach'].indexOf(value) !== -1
      },
    },
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

  computed: {
    uppercaseMode() {
      return startCase(this.mode)
    },
  },
}
</script>
