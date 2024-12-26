<template>
  <FilterContainer>
    <template #filter>
      <div class="flex flex-col gap-2">
        <label class="flex flex-col gap-2">
          <span class="uppercase text-xs font-bold tracking-wide">{{
            `${filter.name} - ${__('From')}`
          }}</span>

          <input
            @change="handleStartDateChange"
            :value="startDateValue"
            class="flex w-full form-control form-input form-control-bordered"
            ref="startField"
            :dusk="`${field.uniqueKey}-range-start`"
            type="datetime-local"
            :placeholder="__('Start')"
          />
        </label>

        <label class="flex flex-col gap-2">
          <span class="uppercase text-xs font-bold tracking-wide">{{
            `${filter.name} - ${__('To')}`
          }}</span>

          <input
            @change="handleEndDateChange"
            :value="endDateValue"
            class="flex w-full form-control form-input form-control-bordered"
            ref="endField"
            :dusk="`${field.uniqueKey}-range-end`"
            type="datetime-local"
            :placeholder="__('End')"
          />
        </label>
      </div>
    </template>
  </FilterContainer>
</template>

<script>
import { DateTime } from 'luxon'
import debounce from 'lodash/debounce'
import filled from '@/util/filled'
import { end } from '@popperjs/core'

export default {
  emits: ['change'],

  props: {
    resourceName: { type: String, required: true },
    filterKey: { type: String, required: true },
    lens: String,
  },

  data: () => ({
    startDateValue: null,
    endDateValue: null,
    debouncedStartDateHandleChange: null,
    debouncedEndDateHandleChange: null,
    debouncedEmit: null,
  }),

  created() {
    this.debouncedEmit = debounce(() => this.emitChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  methods: {
    end() {
      return end
    },
    setCurrentFilterValue() {
      let [startValue, endValue] = this.filter.currentValue || [null, null]

      this.startDateValue = filled(startValue)
        ? DateTime.fromISO(startValue).toFormat("yyyy-MM-dd'T'HH:mm")
        : null

      this.endDateValue = filled(endValue)
        ? DateTime.fromISO(endValue).toFormat("yyyy-MM-dd'T'HH:mm")
        : null
    },

    validateFilter(startValue, endValue) {
      startValue = filled(startValue)
        ? this.toDateTimeISO(startValue, 'start')
        : null

      endValue = filled(endValue) ? this.toDateTimeISO(endValue, 'end') : null

      return [startValue, endValue]
    },

    handleStartDateChange(e) {
      this.startDateValue = e.target.value
      this.debouncedEmit()
    },

    handleEndDateChange(e) {
      this.endDateValue = e.target.value
      this.debouncedEmit()
    },

    emitChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.validateFilter(this.startDateValue, this.endDateValue),
      })
    },

    handleFilterReset() {
      this.$refs.startField.value = ''
      this.$refs.endField.value = ''

      this.setCurrentFilterValue()
    },

    fromDateTimeISO(value) {
      return DateTime.fromISO(value, {
        setZone: true,
      })
        .setZone(this.timezone)
        .toISO()
    },

    toDateTimeISO(value) {
      let isoDate = DateTime.fromISO(value, {
        zone: this.timezone,
        setZone: true,
      })

      return isoDate.setZone(Nova.config('timezone')).toISO()
    },
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },

    field() {
      return this.filter.field
    },

    timezone() {
      return Nova.config('userTimezone') || Nova.config('timezone')
    },
  },
}
</script>
