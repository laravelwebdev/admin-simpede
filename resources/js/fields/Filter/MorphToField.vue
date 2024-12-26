<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <SelectControl
        :dusk="`${field.uniqueKey}-filter`"
        v-model:selected="value"
        @change="value = $event"
        :options="field.morphToTypes"
        label="singularLabel"
      >
        <option value="" :selected="value === ''">&mdash;</option>
      </SelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'
import find from 'lodash/find'
import isNil from 'lodash/isNil'

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
    this.debouncedHandleChange = debounce(() => this.handleChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.setCurrentFilterValue)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue)
  },

  watch: {
    value() {
      this.debouncedHandleChange()
    },
  },

  methods: {
    setCurrentFilterValue() {
      let selectedOption = find(
        this.field.morphToTypes,
        v => v.type === this.filter.currentValue
      )

      this.value = !isNil(selectedOption) ? selectedOption.value : ''
    },

    handleChange() {
      let selectedOption = find(
        this.field.morphToTypes,
        v => v.value === this.value
      )

      this.$emit('change', {
        filterClass: this.filterKey,
        value: !isNil(selectedOption) ? selectedOption.type : '',
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

    hasMorphToTypes() {
      return this.field.morphToTypes.length > 0
    },
  },
}
</script>
