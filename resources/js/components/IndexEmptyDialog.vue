<template>
  <div
    class="flex flex-col justify-center items-center px-6 py-8 space-y-6"
    :dusk="`${resourceName}-empty-dialog`"
  >
    <div class="flex flex-col justify-center items-center px-6 space-y-3">
      <svg
        class="inline-block text-gray-300 dark:text-gray-500"
        xmlns="http://www.w3.org/2000/svg"
        width="65"
        height="51"
        viewBox="0 0 65 51"
      >
        <path
          class="fill-current"
          d="M56 40h2c.552285 0 1 .447715 1 1s-.447715 1-1 1h-2v2c0 .552285-.447715 1-1 1s-1-.447715-1-1v-2h-2c-.552285 0-1-.447715-1-1s.447715-1 1-1h2v-2c0-.552285.447715-1 1-1s1 .447715 1 1v2zm-5.364125-8H38v8h7.049375c.350333-3.528515 2.534789-6.517471 5.5865-8zm-5.5865 10H6c-3.313708 0-6-2.686292-6-6V6c0-3.313708 2.686292-6 6-6h44c3.313708 0 6 2.686292 6 6v25.049375C61.053323 31.5511 65 35.814652 65 41c0 5.522847-4.477153 10-10 10-5.185348 0-9.4489-3.946677-9.950625-9zM20 30h16v-8H20v8zm0 2v8h16v-8H20zm34-2v-8H38v8h16zM2 30h16v-8H2v8zm0 2v4c0 2.209139 1.790861 4 4 4h12v-8H2zm18-12h16v-8H20v8zm34 0v-8H38v8h16zM2 20h16v-8H2v8zm52-10V6c0-2.209139-1.790861-4-4-4H6C3.790861 2 2 3.790861 2 6v4h52zm1 39c4.418278 0 8-3.581722 8-8s-3.581722-8-8-8-8 3.581722-8 8 3.581722 8 8 8z"
        />
      </svg>

      <h3 class="text-base font-normal">
        {{
          __('No :resource matched the given criteria.', {
            resource: singularName,
          })
        }}
      </h3>
    </div>

    <!-- Create / Attach Button -->
    <InertiaButton
      v-if="shouldShowButton"
      variant="outline"
      :href="buttonURL"
      class="shrink-0"
      dusk="create-button"
    >
      <span class="hidden md:inline-block">
        {{ createOrAttachButtonLabel }}
      </span>
      <span class="inline-block md:hidden">
        {{ shouldShowAttachButton ? __('Attach') : __('Create') }}
      </span>
    </InertiaButton>
  </div>
</template>

<script setup>
import { useLocalization } from '@/composables/useLocalization'
import { computed } from 'vue'

const { __ } = useLocalization()

const props = defineProps([
  'create-button-label',
  'singularName',
  'resourceName',
  'viaResource',
  'viaResourceId',
  'viaRelationship',
  'relationshipType',
  'authorizedToCreate',
  'authorizedToRelate',
])

const shouldShowButton = computed(
  () => shouldShowCreateButton.value || shouldShowAttachButton.value
)

const shouldShowAttachButton = computed(() => {
  return (
    (props.relationshipType === 'belongsToMany' ||
      props.relationshipType === 'morphToMany') &&
    props.authorizedToRelate
  )
})

const shouldShowCreateButton = computed(() => {
  return (
    props.authorizedToCreate && props.authorizedToRelate && !props.alreadyFilled
  )
})

const createOrAttachButtonLabel = computed(() => {
  return shouldShowAttachButton.value
    ? __('Attach :resource', { resource: props.singularName })
    : props.createButtonLabel
})

const buttonURL = computed(() => {
  if (shouldShowAttachButton.value) {
    return Nova.url(
      `/resources/${props.viaResource}/${props.viaResourceId}/attach/${props.resourceName}`,
      {
        viaRelationship: props.viaRelationship,
        polymorphic: props.relationshipType === 'morphToMany' ? '1' : '0',
      }
    )
  } else if (shouldShowCreateButton.value) {
    return Nova.url(`/resources/${props.resourceName}/new`, {
      viaResource: props.viaResource,
      viaResourceId: props.viaResourceId,
      viaRelationship: props.viaRelationship,
      relationshipType: props.relationshipType,
    })
  }
})
</script>
