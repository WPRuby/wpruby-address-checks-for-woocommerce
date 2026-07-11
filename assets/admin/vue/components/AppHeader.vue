<template>
  <header class="agl-header">
    <div class="agl-header__brand">
      <div class="agl-header__titlebar">
        <span class="agl-header__logo" aria-hidden="true">
          <img :src="logo" width="36" height="36" alt="">
        </span>
        <h1 class="agl-header__title">{{ title }}</h1>
        <span v-if="version" class="agl-header__version">v{{ version }}</span>
      </div>
      <p class="agl-header__desc">{{ description }}</p>

      <div v-if="ready" class="agl-header__chips">
        <span
          class="agl-chip"
          :class="enabled ? 'agl-chip--enabled' : 'agl-chip--disabled'"
        >
          <span class="agl-chip__dot" aria-hidden="true"></span>
          {{ enabled ? enabledLabel : disabledLabel }}
        </span>
        <span class="agl-chip" :class="behaviorChipClass">
          {{ validationModeLabel }}
        </span>
        <span class="agl-chip agl-chip--checkout">
          {{ checkoutSupportLabel }}
        </span>
      </div>
    </div>

    <div class="agl-header__actions">
      <span
        class="agl-save-status"
        :class="dirty ? 'agl-save-status--dirty' : 'agl-save-status--saved'"
      >
        {{ dirty ? unsavedLabel : savedLabel }}
      </span>
      <button
        type="button"
        class="agl-button agl-button--save"
        :class="{
          'agl-button--save-active': dirty && !saving,
          'agl-button--save-saving': saving,
        }"
        :disabled="saving || !dirty"
        @click="$emit('save')"
      >
        <span v-if="saving" class="agl-button__spinner" aria-hidden="true"></span>
        {{ saving ? savingLabel : saveLabel }}
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { state, validationModeLabel as modeLabel } from '../store.js';
import { __ } from '../api/client.js';

const logo = computed(() => window.addressGuardAdmin?.logo || '');

defineProps({
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
});
defineEmits(['save']);

const boot = window.addressGuardAdmin || {};
const version = boot.version || '';

const title = __('Address Guard for WooCommerce');
const description = __(
  'Prevent common WooCommerce checkout address mistakes before the order is placed.'
);
const saveLabel = __('Save changes');
const savingLabel = __('Saving…');
const unsavedLabel = __('Unsaved changes');
const savedLabel = __('All changes saved');
const enabledLabel = __('Enabled');
const disabledLabel = __('Disabled');

const ready = computed(() => state.ready && !!state.settings);
const enabled = computed(() => !!(state.settings && state.settings.plugin_enabled));

const validationModeLabel = computed(() => {
  const mode = state.settings?.validation_mode || 'warn';
  return `${__('Behavior')}: ${modeLabel(mode)}`;
});

const behaviorChipClass = computed(() => {
  const mode = state.settings?.validation_mode || 'warn';
  return mode === 'block' ? 'agl-chip--block' : 'agl-chip--warn';
});

const checkoutSupportLabel = computed(() => {
  const detected = state.meta.checkout_detected_label;
  if (detected) {
    return `${__('Checkout')}: ${detected}`;
  }
  return __('Checkout support active');
});
</script>
