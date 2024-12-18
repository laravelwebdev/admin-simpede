<template>
  <ResourceLens
    :resourceName="resourceName"
    :lens="lens"
    :searchable="searchable"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'
import ResourceLens from '@/views/Lens'

defineOptions({
  name: 'Lens',
})

defineProps({
  lens: { type: String, required: true },
  searchable: { type: Boolean, default: false },
  ...mapProps(['resourceName']),
})
</script>
