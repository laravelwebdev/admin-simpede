<template>
  <tr
    :data-pivot-id="resource.id.pivotValue"
    :dusk="`${resource.id.value}-row`"
    class="group"
    :class="{
      'divide-x divide-gray-100 dark:divide-gray-700': shouldShowColumnBorders,
    }"
    @click.stop.prevent="handleClick"
  >
    <!-- Resource Selection Checkbox -->
    <td
      v-if="shouldShowCheckboxes"
      :class="{
        'py-2': !shouldShowTight,
        'cursor-pointer': resource.authorizedToView,
      }"
      class="w-[1%] white-space-nowrap pl-5 pr-5 dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
      @click.stop
    >
      <Checkbox
        v-if="shouldShowCheckboxes"
        @change="toggleSelection"
        :model-value="checked"
        :dusk="`${resource.id.value}-checkbox`"
        :aria-label="__('Select Resource :title', { title: resource.title })"
      />
    </td>

    <!-- Fields -->
    <td
      v-for="(field, index) in resource.fields"
      :key="field.uniqueKey"
      :class="{
        'px-6': index === 0 && !shouldShowCheckboxes,
        'px-2': index !== 0 || shouldShowCheckboxes,
        'py-2': !shouldShowTight,
        'whitespace-nowrap': !field.wrapping,
        'cursor-pointer': clickableRow,
      }"
      class="dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
    >
      <component
        :is="'index-' + field.component"
        :class="`text-${field.textAlign}`"
        :field="field"
        :resource="resource"
        :resource-name="resourceName"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
      />
    </td>

    <td
      :class="{
        'py-2': !shouldShowTight,
        'cursor-pointer': resource.authorizedToView,
      }"
      class="px-2 w-[1%] white-space-nowrap text-right align-middle dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900"
    >
      <div class="flex items-center justify-end space-x-0 text-gray-400">
        <InlineActionDropdown
          v-if="shouldShowActionDropdown"
          :actions="availableActions"
          :endpoint="actionsEndpoint"
          :resource="resource"
          :resource-name="resourceName"
          :via-many-to-many="viaManyToMany"
          :via-resource="viaResource"
          :via-resource-id="viaResourceId"
          :via-relationship="viaRelationship"
          @actionExecuted="$emit('actionExecuted')"
          @show-preview="navigateToPreviewView"
        />

        <!-- View Resource Link -->
        <Link
          v-if="authorizedToViewAnyResources"
          :as="!resource.authorizedToView ? 'button' : 'a'"
          v-tooltip.click="__('View')"
          :aria-label="__('View')"
          :dusk="`${resource['id'].value}-view-button`"
          :href="viewURL"
          class="inline-flex items-center justify-center h-9 w-9"
          :class="
            resource.authorizedToView
              ? 'text-gray-500 dark:text-gray-400 hover:[&:not(:disabled)]:text-primary-500 dark:hover:[&:not(:disabled)]:text-primary-500'
              : 'disabled:cursor-not-allowed disabled:opacity-50'
          "
          :disabled="!resource.authorizedToView"
          @click.stop
        >
          <span class="flex items-center gap-1">
            <span>
              <Icon name="eye" type="outline" />
            </span>
          </span>
        </Link>

        <!-- Edit Button -->
        <Link
          v-if="authorizedToUpdateAnyResources"
          :as="!resource.authorizedToUpdate ? 'button' : 'a'"
          v-tooltip.click="viaManyToMany ? __('Edit Attached') : __('Edit')"
          :aria-label="viaManyToMany ? __('Edit Attached') : __('Edit')"
          :dusk="
            viaManyToMany
              ? `${resource['id'].value}-edit-attached-button`
              : `${resource['id'].value}-edit-button`
          "
          :href="updateURL"
          class="inline-flex items-center justify-center h-9 w-9"
          :class="
            resource.authorizedToUpdate
              ? 'text-gray-500 dark:text-gray-400 hover:[&:not(:disabled)]:text-primary-500 dark:hover:[&:not(:disabled)]:text-primary-500'
              : 'disabled:cursor-not-allowed disabled:opacity-50'
          "
          :disabled="!resource.authorizedToUpdate"
          @click.stop
        >
          <span class="flex items-center gap-1">
            <span>
              <Icon name="pencil-square" type="outline" />
            </span>
          </span>
        </Link>

        <!-- Delete Resource Link -->
        <Button
          v-if="
            authorizedToDeleteAnyResources &&
            (!resource.softDeleted || viaManyToMany)
          "
          @click.stop="openDeleteModal"
          v-tooltip.click="__(viaManyToMany ? 'Detach' : 'Delete')"
          :aria-label="__(viaManyToMany ? 'Detach' : 'Delete')"
          :dusk="`${resource.id.value}-delete-button`"
          icon="trash"
          variant="action"
          :disabled="!resource.authorizedToDelete"
        />

        <!-- Restore Resource Link -->
        <Button
          v-if="
            authorizedToRestoreAnyResources &&
            resource.softDeleted &&
            !viaManyToMany
          "
          v-tooltip.click="__('Restore')"
          :aria-label="__('Restore')"
          :disabled="!resource.authorizedToRestore"
          :dusk="`${resource.id.value}-restore-button`"
          type="button"
          @click.stop="openRestoreModal"
          icon="arrow-path"
          variant="action"
        />

        <DeleteResourceModal
          :mode="viaManyToMany ? 'detach' : 'delete'"
          :show="deleteModalOpen"
          @close="closeDeleteModal"
          @confirm="confirmDelete"
        />

        <RestoreResourceModal
          :show="restoreModalOpen"
          @close="closeRestoreModal"
          @confirm="confirmRestore"
        >
          <ModalHeader v-text="__('Restore Resource')" />
          <ModalContent>
            <p class="leading-normal">
              {{ __('Are you sure you want to restore this resource?') }}
            </p>
          </ModalContent>
        </RestoreResourceModal>
      </div>
    </td>
  </tr>

  <PreviewResourceModal
    v-if="previewModalOpen"
    :resource-id="resource.id.value"
    :resource-name="resourceName"
    :show="previewModalOpen"
    @close="closePreviewModal"
    @confirm="closePreviewModal"
  />
</template>

<script>
import filter from 'lodash/filter'
import { Inertia } from '@inertiajs/inertia'
import { mapGetters } from 'vuex'
import { Button, Checkbox, Icon } from 'laravel-nova-ui'

export default {
  components: {
    Button,
    Checkbox,
    Icon,
  },

  emits: ['actionExecuted'],

  inject: [
    'resourceHasId',
    'authorizedToViewAnyResources',
    'authorizedToUpdateAnyResources',
    'authorizedToDeleteAnyResources',
    'authorizedToRestoreAnyResources',
  ],

  props: [
    'actionsAreAvailable',
    'actionsEndpoint',
    'checked',
    'clickAction',
    'deleteResource',
    'queryString',
    'relationshipType',
    'resource',
    'resourceName',
    'resourcesSelected',
    'restoreResource',
    'selectedResources',
    'shouldShowCheckboxes',
    'shouldShowColumnBorders',
    'tableStyle',
    'testId',
    'updateSelectionStatus',
    'viaManyToMany',
    'viaRelationship',
    'viaResource',
    'viaResourceId',
  ],

  data: () => ({
    commandPressed: false,
    deleteModalOpen: false,
    restoreModalOpen: false,
    previewModalOpen: false,
  }),

  beforeMount() {
    this.isSelected = this.selectedResources.indexOf(this.resource) > -1
  },

  mounted() {
    window.addEventListener('keydown', this.handleKeydown)
    window.addEventListener('keyup', this.handleKeyup)
  },

  beforeUnmount() {
    window.removeEventListener('keydown', this.handleKeydown)
    window.removeEventListener('keyup', this.handleKeyup)
  },

  methods: {
    /**
     * Select the resource in the parent component
     */
    toggleSelection() {
      this.updateSelectionStatus(this.resource)
    },

    handleKeydown(e) {
      if (e.key === 'Meta' || e.key === 'Control') {
        this.commandPressed = true
      }
    },

    handleKeyup(e) {
      if (e.key === 'Meta' || e.key === 'Control') {
        this.commandPressed = false
      }
    },

    handleClick(e) {
      if (this.resourceHasId === false) {
        return
      } else if (this.clickAction === 'edit') {
        return this.navigateToEditView(e)
      } else if (this.clickAction === 'select') {
        return this.toggleSelection()
      } else if (this.clickAction === 'ignore') {
        return
      } else if (this.clickAction === 'detail') {
        return this.navigateToDetailView(e)
      } else if (this.clickAction === 'preview') {
        return this.navigateToPreviewView(e)
      } else {
        return this.navigateToDetailView(e)
      }
    },

    navigateToDetailView(e) {
      if (!this.resource.authorizedToView) {
        return
      }
      this.commandPressed
        ? window.open(this.viewURL, '_blank')
        : Inertia.visit(this.viewURL)
    },

    navigateToEditView(e) {
      if (!this.resource.authorizedToUpdate) {
        return
      }
      this.commandPressed
        ? window.open(this.updateURL, '_blank')
        : Inertia.visit(this.updateURL)
    },

    navigateToPreviewView(e) {
      if (!this.resource.authorizedToView) {
        return
      }
      this.openPreviewModal()
    },

    openPreviewModal() {
      this.previewModalOpen = true
    },

    closePreviewModal() {
      this.previewModalOpen = false
    },

    openDeleteModal() {
      this.deleteModalOpen = true
    },

    confirmDelete() {
      this.deleteResource(this.resource)
      this.closeDeleteModal()
    },

    closeDeleteModal() {
      this.deleteModalOpen = false
    },

    openRestoreModal() {
      this.restoreModalOpen = true
    },

    confirmRestore() {
      this.restoreResource(this.resource)
      this.closeRestoreModal()
    },

    closeRestoreModal() {
      this.restoreModalOpen = false
    },
  },

  computed: {
    ...mapGetters(['currentUser']),

    updateURL() {
      if (this.viaManyToMany) {
        return this.$url(
          `/resources/${this.viaResource}/${this.viaResourceId}/edit-attached/${this.resourceName}/${this.resource.id.value}`,
          {
            viaRelationship: this.viaRelationship,
            viaPivotId: this.resource.id.pivotValue,
          }
        )
      }

      return this.$url(
        `/resources/${this.resourceName}/${this.resource.id.value}/edit`,
        {
          viaResource: this.viaResource,
          viaResourceId: this.viaResourceId,
          viaRelationship: this.viaRelationship,
        }
      )
    },

    viewURL() {
      return this.$url(
        `/resources/${this.resourceName}/${this.resource.id.value}`
      )
    },

    availableActions() {
      return filter(this.resource.actions, a => a.showOnTableRow)
    },

    shouldShowTight() {
      return this.tableStyle === 'tight'
    },

    clickableRow() {
      if (this.resourceHasId === false) {
        return false
      } else if (this.clickAction === 'edit') {
        return this.resource.authorizedToUpdate
      } else if (this.clickAction === 'select') {
        return this.shouldShowCheckboxes
      } else if (this.clickAction === 'ignore') {
        return false
      } else if (this.clickAction === 'detail') {
        return this.resource.authorizedToView
      } else if (this.clickAction === 'preview') {
        return this.resource.authorizedToView
      } else {
        return this.resource.authorizedToView
      }
    },

    shouldShowActionDropdown() {
      return this.availableActions.length > 0 || this.userHasAnyOptions
    },

    shouldShowPreviewLink() {
      return this.resource.authorizedToView && this.resource.previewHasFields
    },

    userHasAnyOptions() {
      return (
        this.resourceHasId &&
        (this.resource.authorizedToReplicate ||
          this.shouldShowPreviewLink ||
          this.canBeImpersonated)
      )
    },

    canBeImpersonated() {
      return (
        this.currentUser.canImpersonate && this.resource.authorizedToImpersonate
      )
    },
  },
}
</script>
