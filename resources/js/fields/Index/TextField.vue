<template>
  <div :class="`text-${field.textAlign}`">
    <template v-if="fieldValue">
      <CopyButton
        v-if="
          fieldHasValueOrCustomizedDisplay &&
          field.copyable &&
          !shouldDisplayAsHtml
        "
        @click.prevent.stop="copy"
        v-tooltip="__('Copy to clipboard')"
      >
        <span ref="theFieldValue">
          {{ fieldValue }}
        </span>
      </CopyButton>

      <span
        v-else-if="
          fieldHasValueOrCustomizedDisplay &&
          !field.copyable &&
          !shouldDisplayAsHtml
        "
        class="whitespace-normal"
      >
        {{ fieldValue }}
      </span>
      <div
        @click.stop
        v-else-if="
          fieldHasValueOrCustomizedDisplay &&
          !field.copyable &&
          shouldDisplayAsHtml
        "
        v-html="fieldValue"
      />
      <p v-else>&mdash;</p>
    </template>
    <p v-else>&mdash;</p>
  </div>
</template>

<script>
import { CopiesToClipboard, FieldValue } from '@/mixins'

export default {
  mixins: [CopiesToClipboard, FieldValue],

  props: ['resourceName', 'field'],

  methods: {
    copy() {
      this.copyValueToClipboard(this.field.value)
    },
  },
}
</script>
