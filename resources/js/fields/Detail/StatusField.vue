<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <Badge
        v-if="fieldHasValue"
        class="whitespace-nowrap inline-flex items-center"
        :class="field.typeClass"
      >
        <span class="mr-1 -ml-1">
          <Loader v-if="field.type == 'loading'" width="20" class="mr-1" />
          <Icon
            v-if="field.type == 'failed'"
            name="exclamation-circle"
            type="solid"
          />
          <Icon
            v-if="field.type == 'success'"
            name="check-circle"
            type="solid"
          />
        </span>
        {{ fieldValue }}
      </Badge>

      <span v-else>&mdash;</span>
    </template>
  </PanelItem>
</template>

<script>
import { Icon } from 'laravel-nova-ui'
import { FieldValue } from '@/mixins'

export default {
  components: {
    Icon,
  },

  mixins: [FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
}
</script>
