<template>
  <div id="nova">
    <MainHeader />

    <!-- Content -->
    <div dusk="content">
      <div
        class="hidden lg:block lg:absolute left-0 bottom-0 lg:top-[56px] lg:bottom-auto w-60 px-3 py-8"
      >
        <!-- The Main Menu on desktop gets extra padding to keep the bottom of the sidebar from feeling crowded -->
        <MainMenu class="pb-24" data-screen="desktop" />
      </div>

      <div class="p-4 md:py-8 md:px-12 lg:ml-60 space-y-8">
        <Breadcrumbs v-if="breadcrumbsEnabled" />

        <FadeTransition>
          <slot />
        </FadeTransition>

        <Footer />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useLocalization } from '@/composables/useLocalization'
import { computed, onBeforeUnmount, onMounted } from 'vue'
import MainHeader from '@/layouts/MainHeader'
import Footer from '@/layouts/Footer'

defineOptions({
  name: 'AppLayout',
})

const { __ } = useLocalization()

const handleError = message => {
  Nova.error(message)
}

const handleTokenExpired = () => {
  Nova.$toasted.show(__('Sorry, your session has expired.'), {
    action: {
      onClick: () => Nova.redirectToLogin(),
      text: __('Reload'),
    },
    duration: null,
    type: 'error',
  })

  setTimeout(() => {
    Nova.redirectToLogin()
  }, 5000)
}

const breadcrumbsEnabled = computed(() => Nova.config('breadcrumbsEnabled'))

onMounted(() => {
  Nova.$on('error', handleError)
  Nova.$on('token-expired', handleTokenExpired)
})

onBeforeUnmount(() => {
  Nova.$off('error', handleError)
  Nova.$off('token-expired', handleTokenExpired)
})
</script>
