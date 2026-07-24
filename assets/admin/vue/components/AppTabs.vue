<template>
  <nav class="agl-tabs" :aria-label="tabsLabel">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      type="button"
      class="agl-tab"
      :class="{ 'agl-tab--active': activeTab === tab.id }"
      :aria-current="activeTab === tab.id ? 'page' : undefined"
      @click="$emit('change', tab.id)"
    >
      <span class="agl-tab__icon" aria-hidden="true">
        <svg v-if="tab.id === 'general'" viewBox="0 0 20 20" width="16" height="16" fill="none">
          <path d="M10 2.5 3.5 7v9.5h4.5V12h4v4.5h4.5V7L10 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
        </svg>
        <svg v-else-if="tab.id === 'autocomplete'" viewBox="0 0 20 20" width="16" height="16" fill="none">
          <circle cx="9" cy="9" r="5.25" stroke="currentColor" stroke-width="1.5" />
          <path d="m13.5 13.5 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        <svg v-else-if="tab.id === 'checks'" viewBox="0 0 20 20" width="16" height="16" fill="none">
          <path d="M16.25 5.75 8.5 14.25 3.75 9.75" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <svg v-else-if="tab.id === 'messages'" viewBox="0 0 20 20" width="16" height="16" fill="none">
          <path d="M4 5.5h12v7.5H8l-3.5 2.5V5.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
        </svg>
        <svg v-else-if="tab.id === 'upgrade'" viewBox="0 0 20 20" width="16" height="16" fill="none">
          <path d="M10 3.5 12.2 8l4.8.5-3.6 3.3.9 4.7L10 14.3 5.7 16.5l.9-4.7L3 8.5 7.8 8 10 3.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
        </svg>
      </span>
      <span class="agl-tab__label">{{ tab.label }}</span>
    </button>
  </nav>
</template>

<script setup>
import { __ } from '../api/client.js';

defineProps({
  activeTab: { type: String, required: true },
});
defineEmits(['change']);

const tabsLabel = __('Address Checks sections');

const tabs = [
  { id: 'general', label: __('General') },
  { id: 'autocomplete', label: __('Autocomplete') },
  { id: 'checks', label: __('Checks') },
  { id: 'messages', label: __('Messages') },
  { id: 'upgrade', label: __('Upgrade to Pro') },
];
</script>
