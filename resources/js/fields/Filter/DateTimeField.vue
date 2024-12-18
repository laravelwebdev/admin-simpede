<template>
  <FilterContainer>
    <template #filter>
      <div class="flex flex-col gap-2">
        <label class="flex flex-col gap-2">
          <span class="uppercase text-xs font-bold tracking-wide">{{
            `${filter.name} - ${__('From')}`
          }}</span>

          <input
            ref="startField"
            v-model="startValue"
            type="datetime-local"
            class="w-full flex form-control form-input form-control-bordered"
            :placeholder="__('Start')"
            :dusk="`${filter.uniqueKey}-range-start`"
          />
        </label>

        <label class="flex flex-col gap-2">
          <span class="uppercase text-xs font-bold tracking-wide">{{
            `${filter.name} - ${__('To')}`
          }}</span>

          <input
            ref="endField"
            v-model="endValue"
            type="datetime-local"
            class="w-full flex form-control form-input form-control-bordered"
            :placeholder="__('End')"
            :dusk="`${filter.uniqueKey}-range-end`"
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

export default {
  emits: ['change'],

  props: {
    resourceName: { type: String, required: true },
    filterKey: { type: String, required: true },
    lens: String,
  },

  data: () => ({
    startValue: null,
    endValue: null,

    debouncedEventEmitter: null,
  }),

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  watch: {
    startValue() {
      this.debouncedEventEmitter()
    },

    endValue() {
      this.debouncedEventEmitter()
    },
  },

  methods: {
    setCurrentFilterValue() {
      let [startValue, endValue] = this.filter.currentValue || [null, null]

      this.startValue = filled(startValue)
        ? DateTime.fromISO(startValue).toFormat("yyyy-MM-dd'T'HH:mm")
        : null

      this.endValue = filled(endValue)
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

    emitFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.validateFilter(this.startValue, this.endValue),
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
