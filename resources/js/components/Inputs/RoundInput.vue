<template>
  <input
    v-bind="{ ...$attrs }"
    v-model="keyword"
    class="appearance-none rounded-full h-8 pl-10 w-full focus:bg-white focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600"
  />
</template>

<script setup>
defineOptions({
  inheritAttrs: false,
})

const keyword = defineModel()
</script>
