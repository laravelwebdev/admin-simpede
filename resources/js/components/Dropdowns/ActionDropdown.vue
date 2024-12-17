<template>
  <div>
    <!-- Confirm Action Modal -->
    <component
      v-if="actionModalVisible"
      :show="actionModalVisible"
      class="text-left"
      :is="selectedAction?.component"
      :working="working"
      :selected-resources="selectedResources"
      :resource-name="resourceName"
      :action="selectedAction"
      :errors="errors"
      @confirm="runAction"
      @close="closeConfirmationModal"
    />

    <component
      v-if="responseModalVisible"
      :show="responseModalVisible"
      :is="actionResponseData?.modal"
      @confirm="handleResponseModalConfirm"
      @close="handleResponseModalClose"
      :data="actionResponseData"
    />

    <Dropdown>
      <template #default>
        <slot name="trigger">
          <Button
            @click.stop
            :dusk="triggerDuskAttribute"
            variant="ghost"
            icon="ellipsis-horizontal"
            v-tooltip="__('Actions')"
          />
        </slot>
      </template>

      <template #menu>
        <DropdownMenu width="auto">
          <ScrollWrap :height="250">
            <nav
              class="px-1 divide-y divide-gray-100 dark:divide-gray-800 divide-solid"
            >
              <slot name="menu" />

              <div v-if="actions.length > 0">
                <DropdownMenuHeading v-if="showHeadings">{{
                  __('User Actions')
                }}</DropdownMenuHeading>

                <div class="py-1">
                  <DropdownMenuItem
                    v-for="action in actions"
                    :key="action.uriKey"
                    :data-action-id="action.uriKey"
                    as="button"
                    class="border-none"
                    @click="() => handleClick(action)"
                    :title="action.name"
                    :disabled="action.authorizedToRun === false"
                  >
                    {{ action.name }}
                  </DropdownMenuItem>
                </div>
              </div>
            </nav>
          </ScrollWrap>
        </DropdownMenu>
      </template>
    </Dropdown>
  </div>
</template>

<script setup>
import { useActions } from '@/composables/useActions'
import { useStore } from 'vuex'
const store = useStore()
import { Button } from 'laravel-nova-ui'
import DropdownMenuHeading from './DropdownMenuHeading.vue'

const emitter = defineEmits(['actionExecuted'])

const props = defineProps({
  resourceName: {},
  viaResource: {},
  viaResourceId: {},
  viaRelationship: {},
  relationshipType: {},
  actions: { type: Array, default: [] },
  selectedResources: { type: [Array, String], default: () => [] },
  endpoint: { type: String, default: null },
  triggerDuskAttribute: { type: String, default: null },
  showHeadings: { type: Boolean, default: false },
})

const {
  errors,
  actionModalVisible,
  responseModalVisible,
  openConfirmationModal,
  closeConfirmationModal,
  closeResponseModal,
  handleActionClick,
  selectedAction,
  working,
  executeAction,
  actionResponseData,
} = useActions(props, emitter, store)

const runAction = () => executeAction(() => emitter('actionExecuted'))

const handleClick = action => {
  if (action.authorizedToRun !== false) {
    handleActionClick(action.uriKey)
  }
}

const handleResponseModalConfirm = () => {
  closeResponseModal()
  emitter('actionExecuted')
}

const handleResponseModalClose = () => {
  closeResponseModal()
  emitter('actionExecuted')
}
</script>
