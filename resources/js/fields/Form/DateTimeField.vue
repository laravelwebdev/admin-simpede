<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex items-center">
        <input
          type="datetime-local"
          class="w-56 form-control form-input form-control-bordered"
          ref="dateTimePicker"
          :id="currentField.uniqueKey"
          :dusk="field.attribute"
          :name="field.name"
          :value="formattedDate"
          :class="errorClasses"
          :disabled="currentlyIsReadonly"
          @change="handleChange"
          :min="currentField.min"
          :max="currentField.max"
          :step="currentField.step"
        />

        <span class="ml-3">
          {{ timezone }}
        </span>
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DateTime } from 'luxon'
import { DependentFormField, HandlesValidationErrors } from '@/mixins'
import filled from '@/util/filled'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  data: () => ({
    formattedDate: '',
  }),

  methods: {
    /*
     * Set the initial value for the field
     */
    setInitialValue() {
      if (this.currentField.value != null) {
        let isoDate = DateTime.fromISO(this.currentField.value || this.value, {
          zone: Nova.config('timezone'),
        })

        this.value = isoDate.toString()

        isoDate = isoDate.setZone(this.timezone)

        this.formattedDate = [
          isoDate.toISODate(),
          isoDate.toFormat(this.timeFormat),
        ].join('T')
      }
    },

    /**
     * On save, populate our form data
     */
    fill(formData) {
      this.fillIfVisible(formData, this.fieldAttribute, this.value || '')

      if (this.currentlyIsVisible && filled(this.value)) {
        let isoDate = DateTime.fromISO(this.value, { zone: this.timezone })

        this.formattedDate = [
          isoDate.toISODate(),
          isoDate.toFormat(this.timeFormat),
        ].join('T')
      }
    },

    /**
     * Update the field's internal value
     */
    handleChange(event) {
      let value = event?.target?.value ?? event

      if (filled(value)) {
        let isoDate = DateTime.fromISO(value, { zone: this.timezone })

        this.value = isoDate.setZone(Nova.config('timezone')).toString()
      } else {
        this.value = this.fieldDefaultValue()
      }

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },
  },

  computed: {
    timeFormat() {
      return this.currentField.step % 60 === 0 ? 'HH:mm' : 'HH:mm:ss'
    },

    timezone() {
      return Nova.config('userTimezone') || Nova.config('timezone')
    },
  },
}
</script>
