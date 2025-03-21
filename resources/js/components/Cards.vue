<template>
  <div>
    <button
      v-if="filteredCards.length > 1"
      @click="toggleCollapse"
      class="md:hidden h-8 py-3 mb-3 uppercase tracking-widest font-bold text-xs inline-flex items-center justify-center focus:outline-none focus:ring-primary-200 border-1 border-primary-500 focus:ring focus:ring-offset-4 focus:ring-offset-gray-100 dark:ring-gray-600 dark:focus:ring-offset-gray-900 rounded"
    >
      <span>{{ collapsed ? __('Show Cards') : __('Hide Cards') }}</span>
      <CollapseButton class="ml-1" :collapsed="collapsed" />
    </button>

    <div v-if="filteredCards.length > 0" class="grid md:grid-cols-12 gap-6">
      <CardWrapper
        v-show="!collapsed"
        v-for="card in filteredCards"
        :card="card"
        :dashboard="dashboard"
        :resource="resource"
        :resource-name="resourceName"
        :resource-id="resourceId"
        :key="`${card.component}.${card.uriKey}`"
        :lens="lens"
      />
    </div>
  </div>
</template>

<script>
import { Collapsable } from '@/mixins'
import filled from '@/util/filled'

export default {
  mixins: [Collapsable],

  props: {
    cards: Array,

    resource: {
      type: Object,
      required: false,
    },

    dashboard: {
      type: String,
      required: false,
    },

    resourceName: {
      type: String,
      default: '',
    },

    resourceId: {
      type: [Number, String],
      default: '',
    },

    onlyOnDetail: {
      type: Boolean,
      default: false,
    },

    lens: {
      lens: String,
      default: '',
    },
  },

  data: () => ({ collapsed: false }),

  computed: {
    /**
     * Determine whether to show the cards based on their onlyOnDetail configuration
     */
    filteredCards() {
      if (this.onlyOnDetail) {
        return this.cards.filter(c => c.onlyOnDetail == true)
      }

      return this.cards.filter(c => c.onlyOnDetail == false)
    },

    localStorageKey() {
      let name = this.resourceName

      if (filled(this.dashboard)) {
        name = `dashboard.${this.dashboard}`
      } else {
        if (filled(this.lens)) {
          name = `lens.${name}.${this.lens}`
        } else if (filled(this.resourceId)) {
          name = `resource.${name}.${this.resourceId}`
        } else {
          name = `resource.${name}`
        }
      }

      return `nova.cards.${name}.collapsed`
    },
  },
}
</script>
