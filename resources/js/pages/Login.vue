<template>
  <div>
    <Head :title="__('Log In')" />

    <form
      @submit.prevent="attempt"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 max-w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Welcome Back!') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <label class="block mb-2" for="username">{{ __(usernameLabel) }}</label>
        <input
          v-model="form[username]"
          class="w-full form-control form-input form-control-bordered"
          :class="{ 'form-control-bordered-error': form.errors.has(username) }"
          id="username"
          :type="usernameInputType"
          :name="username"
          autofocus=""
          autocomplete="username"
          required
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has(username)">
          {{ form.errors.first(username) }}
        </HelpText>
      </div>

      <div class="mb-6">
        <label class="block mb-2" for="password">{{ __('Password') }}</label>
        <div class="relative">
            <input
              v-model="form.password"
              class="w-full form-control form-input form-control-bordered pr-10"
              :class="{
              'form-control-bordered-error': form.errors.has('password'),
              }"
              id="password"
              :type="showPassword ? 'text' : 'password'"
              name="password"
              autocomplete="current-password"
              required
            />
            <Icon 
              type="outline" 
              :name="showPassword ? 'eye-slash' : 'eye'" 
              @click="togglePasswordVisibility"
              class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer"
            />
        </div>
        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('password')">
          {{ form.errors.first('password') }}
        </HelpText>
      </div>

      <div class="mb-6">
        <label class="block mb-2" for="year">{{ __('Year') }}</label>
        <select
          v-model="form.year"
          class="form-control form-input form-control-bordered w-full"
          id="year"
          name="year"
          required
        >
        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>     

      <div class="flex mb-6">
        <Checkbox
          @change="() => (form.remember = !form.remember)"
          :model-value="form.remember"
          dusk="remember-button"
          :label="__('Remember me')"
        />

        <div
          v-if="supportsPasswordReset || forgotPasswordPath !== false"
          class="ml-auto"
        >
          <Link
            v-if="forgotPasswordPath === false"
            :href="$url('/password/reset')"
            class="text-gray-500 font-bold no-underline"
            v-text="__('Forgot your password?')"
          />
          <a
            v-else
            :href="forgotPasswordPath"
            class="text-gray-500 font-bold no-underline"
            v-text="__('Forgot your password?')"
          />
        </div>
      </div>

      <Button
        class="w-full flex justify-center"
        type="submit"
        :loading="form.processing"
      >
        <span>
          {{ __('Log In') }}
        </span>
      </Button>
    </form>
  </div>
</template>

<script>
import Auth from '@/layouts/Auth'
import { Button, Checkbox, Icon } from 'laravel-nova-ui'

export default {
  name: 'LoginPage',

  layout: Auth,

  components: {
    Checkbox,
    Button,
    Icon,
  },

  props: {
    username: { type: String, default: 'email' },
    email: { type: String, default: 'email' },
    years: { type: Array, default: []},
  },

  data() {
    return {
      form: Nova.form({
        [this.username]: '',
        password: '',
        year: new Date().getFullYear(),
        remember: false,
      }),
        showPassword: false,
    }
  },

  methods: {

    togglePasswordVisibility() {
      this.showPassword = !this.showPassword
    },

    async attempt() {
      try {
        const { redirect, two_factor } = await this.form.post(
          Nova.url('/login')
        )

        let path = { url: Nova.url('/'), remote: true }

        if (two_factor === true) {
          path = {
            url: Nova.url('/user-security/two-factor-challenge'),
            remote: false,
          }
        } else if (redirect != null) {
          path = { url: redirect, remote: true }
        }

        Nova.visit(path)
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },
  },

  computed: {
    usernameLabel() {
      return 'Username'
    },

    usernameInputType() {
      return 'text'
    },

    supportsPasswordReset() {
      return Nova.config('withPasswordReset')
    },

    forgotPasswordPath() {
      return Nova.config('forgotPasswordPath')
    },
    
  },
}
</script>
