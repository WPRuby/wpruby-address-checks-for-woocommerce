<template>
  <div class="wpruby-ag-callout" :class="`wpruby-ag-callout--${variant}`" role="status">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'info' },
});
</script>
