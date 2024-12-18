<template>
  <div class="tab-group" :dusk="`${panel.attribute}-tab-panel`">
    <div>
      <Heading v-if="panel.showTitle" :level="1" v-text="panel.name" />
      <p
        v-if="panel.helpText"
        class="text-gray-500 text-sm font-semibold italic"
        :class="panel.helpText ? 'mt-2' : 'mt-3'"
        v-html="panel.helpText"
      />
    </div>
    <div
      class="tab-card"
      :class="[panel.showTitle && !panel.showToolbar ? 'mt-3' : '']"
    >
      <TabGroup>
        <TabList :aria-label="panel.name" class="tab-menu">
          <Tab
            v-for="(tab, index) in sortedTabs(tabs)"
            as="template"
            :key="index"
            v-slot="{ selected }"
          >
            <button
              :dusk="`${tab.attribute}-tab-trigger`"
              :class="[
                selected
                  ? 'active text-primary-500 font-bold border-b-2 border-b-primary-500'
                  : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 hover:dark:text-gray-200',
              ]"
              class="tab-item border-gray-200"
            >
              <span class="capitalize">{{ tab.meta.name }}</span>
            </button>
          </Tab>
        </TabList>

        <TabPanels>
          <TabPanel
            v-for="(tab, index) in sortedTabs(tabs)"
            :key="index"
            :label="tab.name"
            :dusk="`${tab.attribute}-tab-content`"
            :class="[tab.attribute, tab.classes, 'tab']"
          >
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
              <KeepAlive v-for="(field, index) in tab.fields" :key="index">
                <component
                  :is="componentName(field)"
                  :class="{
                    'remove-bottom-border': index === tab.fields.length - 1,
                  }"
                  :field="field"
                  :index="index"
                  :resource="resource"
                  :resource-id="resourceId"
                  :resource-name="resourceName"
                  @actionExecuted="$emit('actionExecuted')"
                />
              </KeepAlive>
            </div>
          </TabPanel>
        </TabPanels>
      </TabGroup>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { mapProps } from '@/mixins'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import orderBy from 'lodash/orderBy'

defineEmits(['actionExecuted'])

const props = defineProps({
  name: { type: String, default: 'Panel' },
  panel: { type: Object, required: true },
  resource: { type: Object, required: true },
  ...mapProps([
    'mode',
    'resourceName',
    'resourceId',
    'relatedResourceName',
    'relatedResourceId',
    'viaResource',
    'viaResourceId',
    'viaRelationship',
  ]),
})

const tabs = computed(() => {
  return props.panel.fields.reduce((tabs, field) => {
    if (!(field.tab?.attribute in tabs)) {
      tabs[field.tab.attribute] = {
        name: field.tab,
        attribute: field.tab.attribute,
        position: field.tab.position,
        init: false,
        listable: field.tab.listable,
        fields: [],
        meta: field.tab.meta,
        classes: 'fields-tab',
      }

      if (
        [
          'belongs-to-many-field',
          'has-many-field',
          'has-many-through-field',
          'has-one-through-field',
          'morph-to-many-field',
        ].includes(field.component)
      ) {
        tabs[field.tab.attribute].classes = 'relationship-tab'
      }
    }

    tabs[field.tab.attribute].fields.push(field)

    return tabs
  }, {})
})

function sortedTabs(tabs) {
  return orderBy(tabs, [c => c.position], ['asc'])
}

function componentName(field) {
  return field.prefixComponent ? `detail-${field.component}` : field.component
}
</script>
