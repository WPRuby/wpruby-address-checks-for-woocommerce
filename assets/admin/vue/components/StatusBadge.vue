<template>
  <span class="wpruby-ag-badge" :class="`wpruby-ag-badge--${variant}`">
    <span v-if="dot" class="wpruby-ag-badge__dot" aria-hidden="true"></span>
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'info' },
  label: { type: String, default: '' },
  dot: { type: Boolean, default: false },
});
</script>
