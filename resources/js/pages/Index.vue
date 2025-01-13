<template>
  <ResourceIndex
    :resourceName="resourceName"
    :shouldOverrideMeta="true"
    :shouldEnableShortcut="true"
    :collapsable="false"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'

defineOptions({
  name: 'Index',
})

defineProps(mapProps(['resourceName']))
</script>
