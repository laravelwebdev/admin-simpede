<template>
  <DefaultField
    :field="field"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex items-center">
        <input
          v-bind="extraAttributes"
          ref="theInput"
          v-model="value"
          :id="field.uniqueKey"
          :disabled="isReadonly"
          class="w-full form-control form-input form-control-bordered"
          :dusk="field.attribute"
        />

        <button
          class="rounded inline-flex text-sm ml-3 link-default"
          v-if="field.showCustomizeButton"
          type="button"
          @click="toggleCustomizeClick"
        >
          {{ __('Customize') }}
        </button>
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from '@/mixins'
import debounce from 'lodash/debounce'

export default {
  mixins: [HandlesValidationErrors, FormField],

  data: () => ({
    isListeningToChanges: false,
    debouncedHandleChange: null,
  }),

  mounted() {
    if (this.shouldRegisterInitialListener) {
      this.registerChangeListener()
    }
  },

  beforeUnmount() {
    this.removeChangeListener()
  },

  methods: {
    async fetchPreviewContent(value) {
      const {
        data: { preview },
      } = await Nova.request().post(
        `/nova-api/${this.resourceName}/field/${this.fieldAttribute}/preview`,
        { value }
      )

      return preview
    },

    registerChangeListener() {
      Nova.$on(this.eventName, debounce(this.handleChange, 250))

      this.isListeningToChanges = true
    },

    removeChangeListener() {
      if (this.isListeningToChanges === true) {
        Nova.$off(this.eventName)
      }
    },

    async handleChange(value) {
      this.value = await this.fetchPreviewContent(value)
    },

    toggleCustomizeClick() {
      if (this.field.readonly) {
        this.removeChangeListener()
        this.isListeningToChanges = false
        this.field.readonly = false
        this.field.extraAttributes.readonly = false
        this.field.showCustomizeButton = false
        this.$refs.theInput.focus()
        return
      }

      this.registerChangeListener()
      this.field.readonly = true
      this.field.extraAttributes.readonly = true
    },
  },

  computed: {
    shouldRegisterInitialListener() {
      return !this.field.updating
    },

    eventName() {
      return this.getFieldAttributeChangeEventName(this.field.from)
    },

    placeholder() {
      return this.field.placeholder ?? null
    },

    extraAttributes() {
      return {
        class: this.errorClasses,
        placeholder: this.placeholder,
        ...this.field.extraAttributes,
      }
    },
  },
}
</script>
