<template>
  <div>
    <TagListItem
      v-for="(tag, index) in tags"
      :key="index"
      :index="index"
      :tag="tag"
      :resource-name="resourceName"
      :editable="editable"
      :with-subtitles="withSubtitles"
      :with-preview="withPreview"
      @tag-removed="index => $emit('tag-removed', index)"
    />
  </div>
</template>

<script setup>
defineProps({
  resourceName: { type: String },
  tags: { type: Array, default: [] },
  editable: { type: Boolean, default: true },
  withSubtitles: { type: Boolean, default: true },
  withPreview: { type: Boolean, default: false },
})

defineEmits(['tag-removed', 'click'])
</script>
