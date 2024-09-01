<template>
  <div class="flex relative" :class="$attrs.class">
    <select
      v-bind="defaultAttributes"
      :value="selected"
      @change="handleChange"
      class="w-full block form-control form-control-bordered form-input"
      ref="selectControl"
      :disabled="disabled"
      :class="{
        'h-8 text-xs': size === 'sm',
        'h-7 text-xs': size === 'xs',
        'h-6 text-xs': size === 'xxs',
        'form-control-bordered-error': hasError,
        'form-input-disabled': disabled,
      }"
      :data-disabled="disabled ? 'true' : null"
    >
      <slot />
      <template v-for="(options, group) in groupedOptions">
        <optgroup :label="group" v-if="group" :key="group">
          <option
            v-bind="attrsFor(option)"
            v-for="option in options"
            :key="option.value"
            :selected="isSelected(option)"
            :disabled="isDisabled(option)"
          >
            {{ labelFor(option) }}
          </option>
        </optgroup>
        <template v-else>
          <option
            v-bind="attrsFor(option)"
            v-for="option in options"
            :key="option.value"
            :selected="isSelected(option)"
            :disabled="isDisabled(option)"
          >
            {{ labelFor(option) }}
          </option>
        </template>
      </template>
    </select>

    <IconArrow
      class="pointer-events-none absolute text-gray-700 right-[11px]"
      :class="{
        'top-[15px]': size === 'md',
        'top-[13px]': size === 'sm',
        'top-[11px]': size === 'xs',
        'top-[9px]': size === 'xxs',
      }"
    />
  </div>
</template>

<script>
import groupBy from 'lodash/groupBy'
import map from 'lodash/map'
import omit from 'lodash/omit'

export default {
  emits: ['change'],

  inheritAttrs: false,

  props: {
    hasError: { type: Boolean, default: false },
    label: { default: 'label' },
    options: { type: Array, default: [] },
    disabled: { type: Boolean, default: false },
    selected: {},
    size: {
      type: String,
      default: 'md',
      validator: val => ['xxs', 'xs', 'sm', 'md'].includes(val),
    },
  },

  methods: {
    labelFor(option) {
      return this.label instanceof Function
        ? this.label(option)
        : option[this.label]
    },

    attrsFor(option) {
      return {
        ...(option.attrs || {}),
        ...{ value: option.value },
      }
    },

    isSelected(option) {
      return option.value == this.selected
    },

    isDisabled(option) {
      return option.disabled === true
    },

    handleChange(event) {
      this.$emit('change', event.target.value)
    },

    resetSelection() {
      this.$refs.selectControl.selectedIndex = 0
    },
  },

  computed: {
    defaultAttributes() {
      return omit(this.$attrs, ['class'])
    },

    groupedOptions() {
      return groupBy(this.options, option => option.group || '')
    },
  },
}
</script>
