<template>
  <div class="wpruby-ag-field">
    <label v-if="label" class="wpruby-ag-field__label" :for="fieldId">{{ label }}</label>
    <input
      :id="fieldId"
      :type="type"
      class="wpruby-ag-input"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="wpruby-ag-field__error">{{ error }}</p>
    <p v-else-if="help" class="wpruby-ag-field__help">{{ help }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  help: { type: String, default: '' },
  error: { type: String, default: '' },
  type: { type: String, default: 'text' },
  disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);

const fieldId = computed(
  () =>
    'wpruby-ag-text-' +
    (props.label || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '-') +
    '-' +
    Math.random().toString(36).slice(2, 7)
);
</script>
