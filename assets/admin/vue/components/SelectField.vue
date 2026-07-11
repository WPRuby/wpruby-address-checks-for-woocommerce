<template>
  <div class="wpruby-ag-field wpruby-ag-field--select">
    <label
      v-if="label"
      class="wpruby-ag-field__label"
      :for="fieldId"
    >{{ label }}</label>
    <select
      :id="fieldId"
      class="wpruby-ag-select"
      :value="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <p v-if="help" class="wpruby-ag-field__help">{{ help }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  help: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);

const fieldId = computed(
  () =>
    'wpruby-ag-select-' +
    (props.label || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '-') +
    '-' +
    Math.random().toString(36).slice(2, 7)
);
</script>
