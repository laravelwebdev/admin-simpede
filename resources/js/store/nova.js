import { usePage } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'
import forEach from 'lodash/forEach'
import filled from '@/util/filled'

export default {
  state: () => ({
    baseUri: '/nova',
    currentUser: null,
    mainMenu: [],
    userMenu: [],
    breadcrumbs: [],
    resources: [],
    version: '4.x',
    mainMenuShown: false,
    canLeaveForm: true,
    canLeaveModal: true,
    pushStateWasTriggered: false,
    validLicense: true,
    queryStringParams: {},
    compiledQueryStringParams: '',
  }),

  getters: {
    currentUser: s => s.currentUser,
    currentVersion: s => s.version,
    mainMenu: s => s.mainMenu,
    userMenu: s => s.userMenu,
    breadcrumbs: s => s.breadcrumbs,
    mainMenuShown: s => s.mainMenuShown,
    canLeaveForm: s => s.canLeaveForm,
    canLeaveFormToPreviousPage: s => s.canLeaveForm && !s.pushStateWasTriggered,
    canLeaveModal: s => s.canLeaveModal,
    validLicense: s => s.validLicense,
    queryStringParams: s => s.queryStringParams,
  },

  mutations: {
    allowLeavingForm(state) {
      state.canLeaveForm = true
    },

    preventLeavingForm(state) {
      state.canLeaveForm = false
    },

    allowLeavingModal(state) {
      state.canLeaveModal = true
    },

    preventLeavingModal(state) {
      state.canLeaveModal = false
    },

    triggerPushState(state) {
      Inertia.pushState(Inertia.page)
      Inertia.ignoreHistoryState = true
      state.pushStateWasTriggered = true
    },

    resetPushState(state) {
      state.pushStateWasTriggered = false
    },

    toggleMainMenu(state) {
      state.mainMenuShown = !state.mainMenuShown
      localStorage.setItem('nova.mainMenu.open', state.mainMenuShown)
    },
  },

  actions: {
    async login({ commit, dispatch }, { email, password, remember }) {
      await Nova.request().post(Nova.url('/login'), {
        email,
        password,
        remember,
      })
    },

    async logout({ state }, customLogoutPath) {
      let response = null

      if (!Nova.config('withAuthentication') && customLogoutPath) {
        response = await Nova.request().post(customLogoutPath)
      } else {
        response = await Nova.request().post(Nova.url('/logout'))
      }

      return response?.data?.redirect || null
    },

    async startImpersonating({}, { resource, resourceId }) {
      let response = null

      response = await Nova.request().post(`/nova-api/impersonate`, {
        resource,
        resourceId,
      })

      let redirect = response?.data?.redirect || null

      if (redirect !== null) {
        location.href = redirect
        return
      }

      Nova.visit('/')
    },

    async stopImpersonating({}) {
      let response = null

      response = await Nova.request().delete(`/nova-api/impersonate`)

      let redirect = response?.data?.redirect || null

      if (redirect !== null) {
        location.href = redirect
        return
      }

      Nova.visit('/')
    },

    async assignPropsFromInertia({ state, dispatch }) {
      let config = usePage().props.value.novaConfig || Nova.appConfig
      let { resources, base, version, mainMenu, userMenu } = config

      let user = usePage().props.value.currentUser
      let validLicense = usePage().props.value.validLicense
      let breadcrumbs = usePage().props.value.breadcrumbs

      Nova.appConfig = config
      state.breadcrumbs = breadcrumbs || []
      state.currentUser = user
      state.validLicense = validLicense
      state.resources = resources
      state.baseUri = base
      state.version = version
      state.mainMenu = mainMenu
      state.userMenu = userMenu

      dispatch('syncQueryString')
    },

    async fetchPolicies({ state, dispatch }) {
      await dispatch('assignPropsFromInertia')
    },

    async syncQueryString({ state }) {
      let searchParams = new URLSearchParams(window.location.search)

      state.queryStringParams = Object.fromEntries(searchParams.entries())
      state.compiledQueryStringParams = searchParams.toString()
    },

    async updateQueryString({ state }, value) {
      let searchParams = new URLSearchParams(window.location.search)
      let page = Inertia.page

      forEach(value, (v, i) => {
        if (!filled(v)) {
          searchParams.delete(i)
        } else {
          searchParams.set(i, v || '')
        }
      })

      if (state.compiledQueryStringParams !== searchParams.toString()) {
        if (page.url !== `${window.location.pathname}?${searchParams}`) {
          page.url = `${window.location.pathname}?${searchParams}`

          window.history.pushState(
            page,
            '',
            `${window.location.pathname}?${searchParams}`
          )
        }

        state.compiledQueryStringParams = searchParams.toString()
      }

      Nova.$emit('query-string-changed', searchParams)
      state.queryStringParams = Object.fromEntries(searchParams.entries())

      return new Promise((resolve, reject) => {
        resolve(searchParams)
      })
    },
  },
}
