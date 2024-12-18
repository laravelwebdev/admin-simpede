<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <MultiSelectControl
        v-model="value"
        @update:modelValue="debouncedHandleChange"
        :options="field.options"
        :dusk="filter.uniqueKey"
      >
        <option value="" :selected="value === ''">&mdash;</option>
      </MultiSelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'

export default {
  emits: ['change'],

  props: {
    resourceName: {
      type: String,
      required: true,
    },
    filterKey: {
      type: String,
      required: true,
    },
    lens: String,
  },

  data: () => ({
    value: null,
    debouncedHandleChange: null,
  }),

  created() {
    this.debouncedHandleChange = debounce(() => this.handleFilterChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.setCurrentFilterValue)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue)
  },

  methods: {
    setCurrentFilterValue() {
      this.value = this.filter.currentValue
    },

    handleFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.value,
      })
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
  },
}
</script>
