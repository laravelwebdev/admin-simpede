<template>
  <div
    class="relative rounded-lg rounded-b-lg bg-gray-100 dark:bg-gray-800 bg-clip border border-gray-200 dark:border-gray-700"
    :class="{ 'mr-11': editMode && canDeleteRow }"
  >
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    canDeleteRow: {
      type: Boolean,
      default: true,
    },
    editMode: {
      type: Boolean,
      default: true,
    },
  },
}
</script>
