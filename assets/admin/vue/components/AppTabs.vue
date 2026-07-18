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
      {{ tab.label }}
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
  { id: 'autocomplete', label: __('Autocomplete') },
  { id: 'checks', label: __('Checks') },
  { id: 'messages', label: __('Messages') },
];
</script>
