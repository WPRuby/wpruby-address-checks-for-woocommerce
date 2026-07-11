<template>
  <fieldset class="agl-radio-cards" :disabled="disabled">
    <legend class="screen-reader-text">{{ legend }}</legend>
    <label
      v-for="option in options"
      :key="option.value"
      class="agl-radio-card"
      :class="{ 'agl-radio-card--selected': modelValue === option.value }"
    >
      <input
        v-model="proxy"
        type="radio"
        class="agl-radio-card__input"
        :value="option.value"
        :disabled="disabled"
      />
      <span class="agl-radio-card__indicator" aria-hidden="true">
        <svg v-if="modelValue === option.value" width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M3 7.2L5.8 10L11 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="agl-radio-card__content">
        <span class="agl-radio-card__label">{{ option.label }}</span>
        <span v-if="option.description" class="agl-radio-card__desc">{{ option.description }}</span>
      </span>
    </label>
  </fieldset>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  legend: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const proxy = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});
</script>
