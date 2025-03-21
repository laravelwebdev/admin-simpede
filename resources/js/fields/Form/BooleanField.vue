<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #default="defaultSlotProps">
      <FormLabel
        class="space-x-1"
        :class="{ 'mb-2': defaultSlotProps.hasHelpText }"
        @click.prevent.stop="toggle"
      >
        <span>
          {{ defaultSlotProps.label }}
        </span>
        <span v-if="defaultSlotProps.required" class="text-red-500 text-sm">
          {{ __('*') }}
        </span>
      </FormLabel>
    </template>
    <template #field>
      <Checkbox
        :disabled="currentlyIsReadonly"
        :dusk="currentField.uniqueKey"
        :id="currentField.uniqueKey"
        :model-value="checked"
        :name="field.name"
        @change="toggle"
        class="mt-2"
      />
    </template>
  </DefaultField>
</template>

<script>
import { Checkbox } from 'laravel-nova-ui'
import { DependentFormField, HandlesValidationErrors } from '@/mixins'

export default {
  components: {
    Checkbox,
  },

  mixins: [HandlesValidationErrors, DependentFormField],

  methods: {
    /*
     * Set the initial value for the field
     */
    setInitialValue() {
      this.value = this.currentField.value ?? this.value
    },

    /**
     * Return the field default value.
     */
    fieldDefaultValue() {
      return false
    },

    /**
     * Provide a function that fills a passed FormData object with the
     * field's internal value attribute
     */
    fill(formData) {
      this.fillIfVisible(formData, this.fieldAttribute, this.trueValue)
    },

    toggle() {
      this.value = !this.value

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },
  },

  computed: {
    checked() {
      return Boolean(this.value)
    },

    trueValue() {
      return +this.checked
    },
  },
}
</script>
