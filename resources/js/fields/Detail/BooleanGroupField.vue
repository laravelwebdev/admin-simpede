<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <ul v-if="value.length > 0" class="space-y-2">
        <li
          v-for="(option, index) in value"
          :key="index"
          class="flex items-center rounded-full font-bold text-sm leading-tight space-x-2"
          :class="classes[option.checked]"
        >
          <IconBoolean class="flex-none" :value="option.checked" />
          <span>{{ option.label }}</span>
        </li>
      </ul>
      <span v-else>{{ this.field.noValueText }}</span>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  data: () => ({
    value: [],
    classes: {
      true: 'text-green-500',
      false: 'text-red-500',
    },
  }),

  created() {
    this.field.value = this.field.value || {}

    this.value = this.field.options
      .filter(o => {
        if (this.field.hideFalseValues === true && o.checked === false) {
          return false
        } else if (this.field.hideTrueValues === true && o.checked === true) {
          return false
        }

        return true
      })
      .map(o => {
        return {
          name: o.name,
          label: o.label,
          checked: this.field.value[o.name] || false,
        }
      })
  },
}
</script>
