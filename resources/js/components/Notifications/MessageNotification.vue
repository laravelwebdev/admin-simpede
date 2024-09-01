<template>
  <div
    class="relative flex items-start px-4 gap-4"
    :dusk="`notification-${notification.id}`"
  >
    <div class="shrink-0">
      <Icon :type="icon" :class="notification.iconClass" />
    </div>

    <div class="flex-auto space-y-4">
      <div>
        <div class="flex items-center">
          <div class="flex-auto">
            <p
              class="mr-1 text-gray-600 dark:text-gray-400 leading-normal break-words"
            >
              {{ notification.message }}
            </p>
          </div>
        </div>

        <p class="mt-1 text-xs" :title="notification.created_at">
          {{ notification.created_at_friendly }}
        </p>
      </div>

      <Button
        v-if="hasUrl"
        @click="handleClick"
        :label="notification.actionText"
        size="small"
      />
    </div>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['delete-notification', 'toggle-mark-as-read', 'toggle-notifications'],

  name: 'MessageNotification',

  props: {
    notification: {
      type: Object,
      required: true,
    },
  },

  methods: {
    handleClick() {
      this.$emit('toggle-mark-as-read')
      this.$emit('toggle-notifications')
      this.visit()
    },

    handleDeleteClick() {
      if (
        confirm(this.__('Are you sure you want to delete this notification?'))
      ) {
        this.$emit('delete-notification')
      }
    },

    visit() {
      if (this.hasUrl) {
        return Nova.visit(this.notification.actionUrl, {
          openInNewTab: this.notification.openInNewTab || false,
        })
      }
    },
  },

  computed: {
    icon() {
      return this.notification.icon
    },

    hasUrl() {
      return this.notification.actionUrl
    },
  },
}
</script>
