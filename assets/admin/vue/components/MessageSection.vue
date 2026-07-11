<template>
  <section class="wpruby-ag-message-section">
    <header class="wpruby-ag-message-section__header">
      <h3 class="wpruby-ag-message-section__title">{{ title }}</h3>
      <p v-if="description" class="wpruby-ag-message-section__description">{{ description }}</p>
    </header>
    <div class="wpruby-ag-message-section__cards">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
