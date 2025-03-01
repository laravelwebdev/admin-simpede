<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <ImageLoader
        v-if="shouldShowLoader"
        :src="imageUrl"
        :maxWidth="field.maxWidth || field.detailWidth"
        :rounded="field.rounded"
        :aspect="field.aspect"
      />

      <span v-if="fieldValue && !imageUrl" class="break-words">
        {{ fieldValue }}
      </span>

      <span v-if="!fieldValue && !imageUrl">&mdash;</span>

      <p v-if="shouldShowToolbar" class="flex items-center text-sm mt-3">
        <a
          v-if="field.downloadable"
          :dusk="field.attribute + '-download-link'"
          @keydown.enter.prevent="download"
          @click.prevent="download"
          tabindex="0"
          class="cursor-pointer text-gray-500 inline-flex items-center"
        >
          <Icon name="download" type="micro" class="mr-2" />
          <span class="class mt-1">{{ __('Download') }}</span>
        </a>
      </p>
    </template>
  </PanelItem>
</template>

<script>
import { Icon } from 'laravel-nova-ui'
import { FieldValue } from '@/mixins'

export default {
  components: { Icon },

  mixins: [FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  methods: {
    /**
     * Download the linked file
     */
    download() {
      const { resourceName, resourceId } = this
      const attribute = this.fieldAttribute

      let link = document.createElement('a')
      link.href = `/nova-api/${resourceName}/${resourceId}/download/${attribute}`
      link.download = 'download'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },
  },

  computed: {
    hasValue() {
      return Boolean(this.field.value || this.imageUrl)
    },

    shouldShowLoader() {
      return this.imageUrl
    },

    shouldShowToolbar() {
      return Boolean(this.field.downloadable && this.hasValue)
    },

    imageUrl() {
      return this.field.previewUrl || this.field.thumbnailUrl
    },

    isVaporField() {
      return this.field.component === 'vapor-file-field'
    },
  },
}
</script>
