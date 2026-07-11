<template>
  <header class="wpruby-ag-header">
    <div class="wpruby-ag-header__brand">
      <div class="wpruby-ag-header__titlebar">
        <span class="wpruby-ag-header__logo" aria-hidden="true">
          <img :src="logo" width="36" height="36" alt="Address Guard">
        </span>
        <h1 class="wpruby-ag-header__title">{{ title }}</h1>
        <span v-if="version" class="wpruby-ag-header__version">v{{ version }}</span>
      </div>
      <p class="wpruby-ag-header__desc">{{ description }}</p>

      <div v-if="ready" class="wpruby-ag-header__chips">
        <span
          class="wpruby-ag-chip"
          :class="enabled ? 'wpruby-ag-chip--on' : 'wpruby-ag-chip--off'"
        >
          <span class="wpruby-ag-chip__dot" aria-hidden="true"></span>
          {{ enabled ? enabledLabel : disabledLabel }}
        </span>
        <span class="wpruby-ag-chip">
          {{ validationModeLabel }}
        </span>
        <span class="wpruby-ag-chip wpruby-ag-chip--on">
          {{ checkoutSupportLabel }}
        </span>
      </div>
    </div>

    <div class="wpruby-ag-header__actions">
      <span
        class="wpruby-ag-status"
        :class="dirty ? 'wpruby-ag-status--dirty' : 'wpruby-ag-status--saved'"
      >
        <span class="wpruby-ag-status__dot" aria-hidden="true"></span>
        {{ dirty ? unsavedLabel : savedLabel }}
      </span>
      <button
        type="button"
        class="wpruby-ag-btn wpruby-ag-btn--primary wpruby-ag-header__save"
        :disabled="saving || !dirty"
        @click="$emit('save')"
      >
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

const checkoutSupportLabel = computed(() => {
  const detected = state.meta.checkout_detected_label;
  if (detected) {
    return `${__('Checkout')}: ${detected}`;
  }
  return __('Checkout support active');
});
</script>
