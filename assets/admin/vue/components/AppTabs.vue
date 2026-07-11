<template>
  <nav class="wpruby-ag-tabs" :aria-label="tabsLabel">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      type="button"
      class="wpruby-ag-tab"
      :class="{ 'wpruby-ag-tab--active': activeTab === tab.id }"
      :aria-current="activeTab === tab.id ? 'page' : undefined"
      @click="$emit('change', tab.id)"
    >
      <span class="wpruby-ag-tab__label">{{ tab.label }}</span>
    </button>
  </nav>
</template>

<script setup>
import { __ } from '../api/client.js';

defineProps({
  activeTab: { type: String, required: true },
});
defineEmits(['change']);

const tabsLabel = __('Address Guard sections');

const tabs = [
  { id: 'general', label: __('General') },
  { id: 'checks', label: __('Checks') },
  { id: 'messages', label: __('Messages') },
  { id: 'upgrade', label: __('Upgrade to Pro') },
];
</script>
