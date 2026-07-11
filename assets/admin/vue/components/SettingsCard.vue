<template>
  <section class="wpruby-ag-card" :class="{ 'wpruby-ag-card--compact': compact }">
    <header v-if="title || description || $slots.actions" class="wpruby-ag-card__head">
      <div class="wpruby-ag-card__head-main">
        <h2 v-if="title" class="wpruby-ag-card__title">{{ title }}</h2>
        <p v-if="description" class="wpruby-ag-card__desc">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="wpruby-ag-card__actions">
        <slot name="actions" />
      </div>
    </header>
    <slot />
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  compact: { type: Boolean, default: false },
});
</script>
