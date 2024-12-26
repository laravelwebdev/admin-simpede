import { Errors } from '@/mixins'
import { computed, nextTick, reactive } from 'vue'
import each from 'lodash/each'
import find from 'lodash/find'
import filter from 'lodash/filter'
import isNil from 'lodash/isNil'
import isObject from 'lodash/isObject'
import map from 'lodash/map'
import tap from 'lodash/tap'
import trim from 'lodash/trim'
import { useLocalization } from '@/composables/useLocalization'

const { __ } = useLocalization()

export function useActions(props, emitter, store) {
  const state = reactive({
    working: false,
    errors: new Errors(),
    actionModalVisible: false,
    responseModalVisible: false,
    selectedActionKey: '',
    endpoint: props.endpoint || `/nova-api/${props.resourceName}/action`,
    actionResponseData: null,
  })

  const selectedResources = computed(() => props.selectedResources)

  const selectedAction = computed(() => {
    if (state.selectedActionKey) {
      return find(allActions.value, a => a.uriKey === state.selectedActionKey)
    }
  })

  const allActions = computed(() =>
    props.actions.concat(props.pivotActions?.actions || [])
  )

  const encodedFilters = computed(
    () => store.getters[`${props.resourceName}/currentEncodedFilters`]
  )

  const searchParameter = computed(() =>
    props.viaRelationship
      ? props.viaRelationship + '_search'
      : props.resourceName + '_search'
  )

  const currentSearch = computed(
    () => store.getters.queryStringParams[searchParameter.value] || ''
  )

  const trashedParameter = computed(() =>
    props.viaRelationship
      ? props.viaRelationship + '_trashed'
      : props.resourceName + '_trashed'
  )

  const currentTrashed = computed(
    () => store.getters.queryStringParams[trashedParameter.value] || ''
  )

  const availableActions = computed(() => {
    return filter(
      props.actions,
      action => selectedResources.value.length > 0 && !action.standalone
    )
  })

  const availablePivotActions = computed(() => {
    if (!props.pivotActions) {
      return []
    }

    return filter(props.pivotActions.actions, action => {
      if (selectedResources.value.length === 0) {
        return action.standalone
      }

      return true
    })
  })

  const hasPivotActions = computed(() => availablePivotActions.value.length > 0)

  const selectedActionIsPivotAction = computed(() => {
    return (
      hasPivotActions.value &&
      Boolean(find(props.pivotActions.actions, a => a === selectedAction.value))
    )
  })

  const actionRequestQueryString = computed(() => {
    return {
      action: state.selectedActionKey,
      pivotAction: selectedActionIsPivotAction.value,
      search: currentSearch.value,
      filters: encodedFilters.value,
      trashed: currentTrashed.value,
      viaResource: props.viaResource,
      viaResourceId: props.viaResourceId,
      viaRelationship: props.viaRelationship,
    }
  })

  const actionFormData = computed(() => {
    return tap(new FormData(), formData => {
      if (selectedResources.value === 'all') {
        formData.append('resources', 'all')
      } else {
        let pivotIds = filter(
          map(selectedResources.value, resource =>
            isObject(resource) ? resource.id.pivotValue : null
          )
        )

        each(selectedResources.value, resource =>
          formData.append(
            'resources[]',
            isObject(resource) ? resource.id.value : resource
          )
        )

        if (
          selectedResources.value !== 'all' &&
          selectedActionIsPivotAction.value === true &&
          pivotIds.length > 0
        ) {
          each(pivotIds, pivotId => formData.append('pivots[]', pivotId))
        }
      }

      each(selectedAction.value.fields, field => {
        field.fill(formData)
      })
    })
  })

  function determineActionStrategy() {
    if (selectedAction.value.withoutConfirmation) {
      executeAction()
    } else {
      openConfirmationModal()
    }
  }

  function openConfirmationModal() {
    state.actionModalVisible = true
  }

  function closeConfirmationModal() {
    state.actionModalVisible = false
  }

  function openResponseModal() {
    state.responseModalVisible = true
  }

  function closeResponseModal() {
    state.responseModalVisible = false
  }

  function emitResponseCallback(callback) {
    emitter('actionExecuted')
    Nova.$emit('action-executed')

    if (typeof callback === 'function') {
      callback()
    }
  }

  function showActionResponseMessage(data) {
    if (data.danger) {
      return Nova.error(data.danger)
    }

    Nova.success(data.message || __('The action was executed successfully.'))
  }

  function executeAction(then) {
    state.working = true
    Nova.$progress.start()

    let responseType = selectedAction.value.responseType ?? 'json'

    Nova.request({
      method: 'post',
      url: state.endpoint,
      params: actionRequestQueryString.value,
      data: actionFormData.value,
      responseType,
    })
      .then(async response => {
        closeConfirmationModal()
        handleActionResponse(response.data, response.headers, then)
      })
      .catch(error => {
        if (error.response && error.response.status === 422) {
          if (responseType === 'blob') {
            error.response.data.text().then(data => {
              state.errors = new Errors(JSON.parse(data).errors)
            })
          } else {
            state.errors = new Errors(error.response.data.errors)
          }

          Nova.error(__('There was a problem executing the action.'))
        }
      })
      .finally(() => {
        state.working = false
        Nova.$progress.done()
      })
  }

  function handleActionResponse(data, headers, then) {
    let contentDisposition = headers['content-disposition']

    if (
      data instanceof Blob &&
      isNil(contentDisposition) &&
      data.type === 'application/json'
    ) {
      data.text().then(jsonStringData => {
        handleActionResponse(JSON.parse(jsonStringData), headers)
      })

      return
    }

    if (data instanceof Blob) {
      return emitResponseCallback(async () => {
        let fileName = 'unknown'

        if (contentDisposition) {
          let fileNameMatch = contentDisposition
            .split(';')[1]
            .match(/filename=(.+)/)
          if (fileNameMatch.length === 2) fileName = trim(fileNameMatch[1], '"')
        }

        await nextTick(() => {
          let url = window.URL.createObjectURL(new Blob([data]))
          let link = document.createElement('a')

          link.href = url
          link.setAttribute('download', fileName)
          document.body.appendChild(link)
          link.click()
          link.remove()

          window.URL.revokeObjectURL(url)
        })
      })
    }

    if (data.modal) {
      state.actionResponseData = data

      showActionResponseMessage(data)

      return openResponseModal()
    }

    if (data.download) {
      return emitResponseCallback(async () => {
        showActionResponseMessage(data)

        await nextTick(() => {
          let link = document.createElement('a')
          link.href = data.download
          link.download = data.name
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
        })
      })
    }

    if (data.deleted) {
      return emitResponseCallback(() => showActionResponseMessage(data))
    }

    if (data.redirect) {
      window.location = data.redirect
    }

    if (data.visit) {
      showActionResponseMessage(data)

      return Nova.visit({
        url: Nova.url(data.visit.path, data.visit.options),
        remote: false,
      })
    }

    if (data.openInNewTab) {
      return emitResponseCallback(() =>
        window.open(data.openInNewTab, '_blank')
      )
    }

    emitResponseCallback(() => showActionResponseMessage(data))
  }

  function handleActionClick(uriKey) {
    state.selectedActionKey = uriKey
    determineActionStrategy()
  }

  function setSelectedActionKey(key) {
    state.selectedActionKey = key
  }

  return {
    errors: computed(() => state.errors),
    working: computed(() => state.working),
    actionModalVisible: computed(() => state.actionModalVisible),
    responseModalVisible: computed(() => state.responseModalVisible),
    selectedActionKey: computed(() => state.selectedActionKey),
    determineActionStrategy,
    setSelectedActionKey,
    openConfirmationModal,
    closeConfirmationModal,
    openResponseModal,
    closeResponseModal,
    handleActionClick,
    selectedAction,
    allActions,
    availableActions,
    availablePivotActions,
    executeAction,
    actionResponseData: computed(() => state.actionResponseData),
  }
}
