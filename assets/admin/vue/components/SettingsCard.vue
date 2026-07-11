<template>
  <section class="agl-card" :class="{ 'agl-card--compact': compact }">
    <header v-if="title || description || $slots.actions" class="agl-card__head">
      <div class="agl-card__head-main">
        <h3 v-if="title" class="agl-card__title">{{ title }}</h3>
        <p v-if="description" class="agl-card__desc">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="agl-card__actions">
        <slot name="actions" />
      </div>
    </header>
    <div class="agl-card__body">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  compact: { type: Boolean, default: false },
});
</script>
