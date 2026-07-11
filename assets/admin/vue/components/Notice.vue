<template>
  <div
    class="wpruby-ag-notice"
    :class="`wpruby-ag-notice--${notice.type}`"
    role="status"
    aria-live="polite"
  >
    <span>{{ notice.message }}</span>
    <button
      type="button"
      class="wpruby-ag-notice__close"
      :aria-label="closeLabel"
      @click="$emit('close')"
    >
      ×
    </button>
  </div>
</template>

<script setup>
import { __ } from '../api/client.js';

defineProps({
  notice: { type: Object, required: true },
});
defineEmits(['close']);

const closeLabel = __('Dismiss');
</script>
