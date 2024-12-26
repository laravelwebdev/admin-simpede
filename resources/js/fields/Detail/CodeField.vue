<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <div
        v-if="fieldValue"
        class="form-input form-control-bordered px-0 overflow-hidden"
      >
        <textarea ref="theTextarea" />
      </div>
      <p v-else>&mdash;</p>
    </template>
  </PanelItem>
</template>

<script>
import CodeMirror from 'codemirror'
import { FieldValue } from '@/mixins'
import isNull from 'lodash/isNull'

export default {
  mixins: [FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  codemirror: null,

  /**
   * Mount the component.
   */
  mounted() {
    const fieldValue = this.fieldValue

    if (!isNull(fieldValue)) {
      const config = {
        tabSize: 4,
        indentWithTabs: true,
        lineWrapping: true,
        lineNumbers: true,
        theme: 'dracula',
        ...this.field.options,
        readOnly: true,
        tabindex: '-1', // The editor is for display only and should not be tabbable.
      }

      this.codemirror = CodeMirror.fromTextArea(this.$refs.theTextarea, config)
      this.codemirror?.getDoc().setValue(fieldValue)
      this.codemirror?.setSize('100%', this.field.height)
    }
  },
}
</script>
