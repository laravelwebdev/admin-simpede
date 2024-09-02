<template>
  <tr class="group">
    <td
      v-if="row.icon"
      class="pl-6 w-14 pr-2"
      :class="{
        [row.iconClass]: true,
        [rowClasses]: true,
        'text-gray-400 dark:text-gray-600': !row.iconClass,
      }"
    >
      <Heroicon :type="row.icon" />
    </td>

    <td
      class="px-2 w-auto"
      :class="{
        [rowClasses]: true,
        'pl-6': !row.icon,
        'pr-6': !row.editUrl || !row.viewUrl,
      }"
    >
      <h2 class="text-base text-gray-500 truncate">
        {{ row.title }}
      </h2>
      <p class="text-gray-400 text-xs truncate">{{ row.subtitle }}</p>
    </td>

    <td
      v-if="row.actions.length > 0"
      class="text-right pr-4 w-12"
      :class="rowClasses"
    >
      <div class="flex justify-end items-center text-gray-400">
        <Dropdown>
          <Button
            icon="ellipsis-horizontal"
            variant="action"
            :aria-label="__('Resource Row Dropdown')"
          />

          <template #menu>
            <DropdownMenu width="auto" class="px-1">
              <ScrollWrap
                :height="250"
                class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid"
              >
                <div class="py-1">
                  <DropdownMenuItem
                    v-bind="actionAttributes(action)"
                    v-for="action in row.actions"
                  >
                    {{ action.name }}
                  </DropdownMenuItem>
                </div>
              </ScrollWrap>
            </DropdownMenu>
          </template>
        </Dropdown>
      </div>
    </td>
  </tr>
</template>

<script>
import isNull from 'lodash/isNull'
import omitBy from 'lodash/omitBy'
import { Button, Icon } from 'laravel-nova-ui'
import Heroicon from '@/components/Icons/Icon'

export default {
  components: {
    Button,
    Icon,
    Heroicon,
  },

  props: {
    row: {
      type: Object,
      required: true,
    },
  },

  methods: {
    actionAttributes(item) {
      let method = item.method || 'GET'

      if (item.external && item.method == 'GET') {
        return {
          as: 'external',
          href: item.path,
          name: item.name,
          title: item.name,
          target: item.target || null,
          external: true,
        }
      }

      return omitBy(
        {
          as: method === 'GET' ? 'link' : 'form-button',
          href: item.path,
          method: method !== 'GET' ? method : null,
          data: item.data || null,
          headers: item.headers || null,
        },
        isNull
      )
    },
  },

  computed: {
    rowClasses() {
      return ['py-2']
    },
  },
}
</script>
