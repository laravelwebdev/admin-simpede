<script setup>
import { Button } from 'laravel-nova-ui'

const props = defineProps({
  href: { type: String, required: true },
  variant: { type: String, default: 'primary' },
  icon: { type: String, default: 'primary' },
  dusk: { type: String, default: null },
  label: { type: String, default: null },
})
</script>

<template>
  <Link :href="href" :dusk="dusk">
    <Button as="div" :variant="variant" :icon="icon" :label="label">
      <slot />
    </Button>
  </Link>
</template>
