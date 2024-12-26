<script setup>
import { useStore } from 'vuex'
import { computed } from 'vue'
import Badge from './Badges/Badge.vue'
import identity from 'lodash/identity'
import isNull from 'lodash/isNull'
import omitBy from 'lodash/omitBy'
import pickBy from 'lodash/pickBy'
import { useLocalization } from '../composables/useLocalization'
import { Inertia } from '@inertiajs/inertia'

const { __ } = useLocalization()

const store = useStore()

const formattedItems = computed(() => {
  return store.getters.userMenu.map(i => {
    let method = i.method || 'GET'
    let props = { href: i.path }

    if (i.external && method === 'GET') {
      return {
        component: 'a',
        props: {
          ...props,
          target: i.target || null,
        },
        name: i.name,
        external: i.external,
        on: {},
      }
    }

    return {
      component: method === 'GET' ? 'a' : 'FormButton',
      props: pickBy(
        omitBy(
          {
            ...props,
            method: method !== 'GET' ? method : null,
            data: i.data || null,
            headers: i.headers || null,
          },
          isNull
        ),
        identity
      ),
      external: i.external,
      name: i.name,
      on: {},
      badge: i.badge,
    }
  })
})

const userName = computed(() => {
  return (
    store.getters.currentUser?.name ||
    store.getters.currentUser?.email ||
    __('Nova User')
  )
})

const customLogoutPath = computed(() => Nova.config('customLogoutPath'))

const supportsAuthentication = computed(() => {
  return (
    Nova.config('withAuthentication') === true ||
    customLogoutPath.value !== false
  )
})

const hasUserMenu = computed(() => {
  return (
    store.getters.currentUser &&
    (formattedItems.value.length > 0 ||
      supportsAuthentication.value ||
      store.getters.currentUser?.impersonating)
  )
})

const handleStopImpersonating = () => {
  if (confirm(__('Are you sure you want to stop impersonating?'))) {
    store.dispatch('stopImpersonating')
  }
}

const attempt = async () => {
  if (confirm(__('Are you sure you want to log out?'))) {
    store
      .dispatch('logout', Nova.config('customLogoutPath'))
      .then(redirect => {
        if (redirect !== null) {
          location.href = redirect
          return
        }

        Nova.redirectToLogin()
      })
      .catch(() => Inertia.reload())
  }
}
</script>

<template>
  <div class="md:hidden bg-gray-100 dark:bg-gray-900/30 rounded-lg py-4 px-2">
    <div class="flex flex-col gap-2">
      <div class="inline-flex items-center shrink-0 gap-2 px-2">
        <Icon
          type="finger-print"
          :solid="true"
          v-if="store.getters.currentUser?.impersonating"
          class="w-7 h-7"
        />
        <img
          v-else-if="store.getters.currentUser?.avatar"
          :alt="__(':name\'s Avatar', { name: userName })"
          :src="store.getters.currentUser?.avatar"
          class="rounded-full w-7 h-7"
        />

        <span class="font-bold whitespace-nowrap">
          {{ userName }}
        </span>
      </div>

      <nav class="flex flex-col">
        <component
          :is="item.component"
          v-for="item in formattedItems"
          :key="item.path"
          v-bind="item.props"
          v-on="item.on"
          class="py-2 px-2 text-gray-600 dark:text-gray-400 hover:opacity-50"
        >
          <span v-if="item.badge" class="mr-1">
            <Badge :extra-classes="item.badge.typeClass">
              {{ item.badge.value }}
            </Badge>
          </span>

          {{ item.name }}
        </component>

        <button
          type="button"
          v-if="store.getters.currentUser?.impersonating"
          @click="handleStopImpersonating"
          class="block w-full py-2 px-2 text-gray-600 dark:text-gray-400 hover:opacity-50 text-left"
        >
          {{ __('Stop Impersonating') }}
        </button>

        <button
          v-if="supportsAuthentication"
          @click="attempt"
          type="button"
          class="block w-full py-2 px-2 text-gray-600 dark:text-gray-400 hover:opacity-50 text-left"
        >
          {{ __('Logout') }}
        </button>
      </nav>
    </div>
  </div>
</template>
