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
          :value="value"
          @blur="handleChangesOnBlurEvent"
          :id="field.uniqueKey"
          :disabled="isReadonly"
          class="w-full form-control form-input form-control-bordered"
          :dusk="field.attribute"
          autocomplete="off"
          spellcheck="false"
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
import {
  FormField,
  HandlesFieldPreviews,
  HandlesValidationErrors,
} from '@/mixins'
import debounce from 'lodash/debounce'
import isNil from 'lodash/isNil'

export default {
  mixins: [FormField, HandlesFieldPreviews, HandlesValidationErrors],

  data: () => ({
    isListeningToChanges: false,
    debouncedHandleChange: null,
  }),

  mounted() {
    this.debouncedHandleChange = debounce(this.handleChange, 250)
    this.registerChangeListener()
  },

  beforeUnmount() {
    this.removeChangeListener()
  },

  methods: {
    registerChangeListener() {
      if (this.shouldRegisterInitialListener === true) {
        Nova.$on(this.eventName, this.debouncedHandleChange)

        this.isListeningToChanges = true
      }
    },

    removeChangeListener() {
      if (this.isListeningToChanges === true) {
        Nova.$off(this.eventName)
      }
    },

    async handleChangesOnBlurEvent(event) {
      let value = event?.target?.value ?? event

      if (this.isReadonly) {
        return
      }

      if (isNil(this.field.from)) {
        this.debouncedHandleChange(value)
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
      return this.field.shouldListenToFromChanges
    },

    eventName() {
      return this.getFieldAttributeChangeEventName(this.field.from)
    },

    placeholder() {
      if (isNil(this.field.from)) {
        return this.field.placeholder ?? this.field.name
      }

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
