<template>
  <div class="wpruby-ag-empty">
    <p class="wpruby-ag-empty__title">{{ title }}</p>
    <p v-if="description" class="wpruby-ag-empty__desc">{{ description }}</p>
    <div v-if="$slots.actions" class="wpruby-ag-empty__actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
