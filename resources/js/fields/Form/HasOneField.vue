<template>
  <Card>
    <LoadingView :loading="loading">
      <template v-if="isEditing">
        <component
          v-for="(field, index) in availableFields"
          :index="index"
          :key="index"
          :is="`form-${field.component}`"
          :errors="errors"
          :resource-id="resourceId"
          :resource-name="resourceName"
          :field="field"
          :via-resource="viaResource"
          :via-resource-id="viaResourceId"
          :via-relationship="viaRelationship"
          :shown-via-new-relation-modal="false"
          :form-unique-id="formUniqueId"
          @field-changed="$emit('field-changed')"
          @file-deleted="handleFileDeleted"
          @file-upload-started="$emit('file-upload-started')"
          @file-upload-finished="$emit('file-upload-finished')"
          :show-help-text="showHelpText"
        />
      </template>
      <div v-else class="flex flex-col justify-center items-center px-6 py-8">
        <button
          class="focus:outline-none focus:ring rounded border-2 border-primary-300 dark:border-gray-500 hover:border-primary-500 active:border-primary-400 dark:hover:border-gray-400 dark:active:border-gray-300 bg-white dark:bg-transparent text-primary-500 dark:text-gray-400 px-3 h-9 inline-flex items-center font-bold shrink-0"
          :dusk="`create-${field.attribute}-relation-button`"
          @click.prevent="showEditForm"
          type="button"
        >
          <span class="hidden md:inline-block">
            {{ __('Create :resource', { resource: field.singularLabel }) }}
          </span>
          <span class="inline-block md:hidden">
            {{ __('Create') }}
          </span>
        </button>
      </div>
    </LoadingView>
  </Card>
</template>

<script>
import {
  TogglesTrashed,
  PerformsSearches,
  FormField,
  HandlesValidationErrors,
  mapProps,
} from '@/mixins'
import InlineFormData from './InlineFormData'
import map from 'lodash/map'
import reject from 'lodash/reject'
import tap from 'lodash/tap'

export default {
  emits: [
    'field-changed',
    'update-last-retrieved-at-timestamp',
    'file-upload-started',
    'file-upload-finished',
  ],

  mixins: [HandlesValidationErrors, FormField],

  provide() {
    return {
      removeFile: this.removeFile,
    }
  },

  props: {
    ...mapProps([
      'resourceName',
      'resourceId',
      'viaResource',
      'viaResourceId',
      'viaRelationship',
    ]),

    field: {
      type: Object,
    },

    formUniqueId: {
      type: String,
    },

    errors: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      loading: false,
      isEditing: this.field.hasOneId !== null || this.field.required === true,
      fields: [],
    }
  },

  /**
   * Mount the component.
   */
  mounted() {
    this.initializeComponent()
  },

  methods: {
    initializeComponent() {
      this.getFields()

      this.field.fill = this.fill
    },

    removeFile(attribute) {
      const { resourceName, resourceId } = this

      Nova.request().delete(
        `/nova-api/${resourceName}/${resourceId}/field/${attribute}`
      )
    },

    fill(formData) {
      if (this.isEditing && this.isVisible) {
        tap(new InlineFormData(this.fieldAttribute, formData), form => {
          this.availableFields.forEach(field => {
            field.fill(form)
          })
        })
      }
    },

    /**
     * Get the available fields for the resource.
     */
    async getFields() {
      this.loading = true

      this.panels = []
      this.fields = []

      try {
        const {
          data: { title, panels, fields },
        } = await Nova.request().get(this.getFieldsEndpoint, {
          params: {
            editing: true,
            editMode: this.editMode,
            viaResource: this.viaResource,
            viaResourceId: this.viaResourceId,
            viaRelationship: this.viaRelationship,
            relationshipType: this.field.relationshipType,
          },
        })

        this.fields = map(fields, field => {
          if (
            field.resourceName === this.field.from.viaResource &&
            field.relationshipType === 'belongsTo' &&
            (this.editMode === 'create' ||
              field.belongsToId.toString() ===
                this.field.from.viaResourceId.toString())
          ) {
            field.visible = false
            field.fill = () => {}
          } else if (
            field.relationshipType === 'morphTo' &&
            (this.editMode === 'create' ||
              (field.resourceName === this.field.from.viaResource &&
                field.morphToId.toString() ===
                  this.field.from.viaResourceId.toString()))
          ) {
            field.visible = false
            field.fill = () => {}
          }

          field.validationKey = `${this.fieldAttribute}.${field.validationKey}`

          return field
        })

        this.loading = false

        Nova.$emit('resource-loaded', {
          resourceName: this.resourceName,
          resourceId: this.resourceId ? this.resourceId.toString() : null,
          mode: this.editMode,
        })
      } catch (error) {
        if ([403, 404].includes(error.response.status)) {
          Nova.error(this.__('There was a problem fetching the resource.'))
        }
      }
    },

    showEditForm() {
      this.isEditing = true
    },

    handleFileDeleted() {
      this.$emit('update-last-retrieved-at-timestamp')
    },
  },

  computed: {
    availableFields() {
      return reject(this.fields, field => {
        return (
          (['relationship-panel'].includes(field.component) &&
            ['hasOne', 'morphOne'].includes(
              field.fields[0].relationshipType
            )) ||
          field.readonly
        )
      })
    },

    getFieldsEndpoint() {
      if (this.editMode === 'update') {
        return `/nova-api/${this.resourceName}/${this.resourceId}/update-fields`
      }

      return `/nova-api/${this.resourceName}/creation-fields`
    },

    editMode() {
      return this.field.hasOneId === null ? 'create' : 'update'
    },
  },
}
</script>
