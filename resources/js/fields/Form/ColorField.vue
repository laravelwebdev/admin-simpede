<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <input
        v-bind="defaultAttributes"
        class="p-2 form-control form-input form-control-bordered bg-white"
        type="color"
        @input="handleChange"
        :value="value"
        :id="currentField.uniqueKey"
        :dusk="field.attribute"
        :disabled="currentlyIsReadonly"
      />

      <datalist v-if="suggestions.length > 0" :id="suggestionsId">
        <option
          :key="suggestion"
          v-for="suggestion in suggestions"
          :value="suggestion"
        />
      </datalist>
    </template>
  </DefaultField>
</template>

<script>
import {
  DependentFormField,
  FieldSuggestions,
  HandlesValidationErrors,
} from '@/mixins'

export default {
  mixins: [DependentFormField, FieldSuggestions, HandlesValidationErrors],

  computed: {
    defaultAttributes() {
      return {
        class: this.errorClasses,
        ...this.suggestionsAttributes,
      }
    },
  },
}
</script>
