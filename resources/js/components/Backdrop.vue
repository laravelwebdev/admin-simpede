<template>
  <div
    v-bind="$attrs"
    v-show="props.show"
    class="absolute inset-0 h-full"
    :style="{ top: `${scrollY}px` }"
  />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const scrollY = ref()
const scrollEvent = () => {
  scrollY.value = window.scrollY
}

onMounted(() => {
  scrollEvent()

  document.addEventListener('scroll', scrollEvent)
})

onBeforeUnmount(() => {
  document.removeEventListener('scroll', scrollEvent)
})
</script>

<script>
export default {
  inheritAttrs: false,
}
</script>
