<template>
  <PanelItem :index="index" :field="field">
    <template v-slot:value>
      <span
        v-for="item in fieldValues"
        v-text="item"
        class="inline-block text-sm mb-1 mr-2 px-2 py-0 bg-primary-500 text-white dark:text-gray-900 rounded"
      />
    </template>
  </PanelItem>
</template>

<script>
import { FieldValue } from '@/mixins'
import forEach from 'lodash/forEach'

export default {
  mixins: [FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  computed: {
    fieldValues() {
      let selected = []

      forEach(this.field.options, option => {
        if (this.isEqualsToValue(option.value)) {
          selected.push(option.label)
        }
      })

      return selected
    },
  },
}
</script>
