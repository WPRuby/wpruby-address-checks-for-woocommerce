<template>
  <div class="wpruby-ag-field">
    <label v-if="label" class="wpruby-ag-field__label" :for="fieldId">{{ label }}</label>
    <textarea
      :id="fieldId"
      ref="textareaRef"
      class="wpruby-ag-textarea"
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      :disabled="disabled"
      @input="$emit('update:modelValue', $event.target.value)"
      @focus="$emit('focus', $event)"
      @blur="$emit('blur', $event)"
    ></textarea>
    <p v-if="error" class="wpruby-ag-field__error">{{ error }}</p>
    <p v-else-if="help" class="wpruby-ag-field__help">{{ help }}</p>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue';
import { formatPlaceholderInsertion } from '../utils/placeholderInsertion.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  help: { type: String, default: '' },
  error: { type: String, default: '' },
  rows: { type: Number, default: 3 },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'focus', 'blur']);

const textareaRef = ref(null);

function insertAtCursor(text) {
  const el = textareaRef.value;
  if (!el) {
    return false;
  }

  const start = el.selectionStart ?? 0;
  const end = el.selectionEnd ?? 0;
  const value = props.modelValue ?? '';
  const { insertion, cursorPos } = formatPlaceholderInsertion(value, start, end, text);
  const nextValue = value.slice(0, start) + insertion + value.slice(end);

  emit('update:modelValue', nextValue);

  nextTick(() => {
    el.focus();
    el.setSelectionRange(cursorPos, cursorPos);
  });

  return true;
}

function focus() {
  textareaRef.value?.focus();
}

defineExpose({ insertAtCursor, focus });

const fieldId = computed(
  () =>
    'wpruby-ag-textarea-' +
    (props.label || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '-') +
    '-' +
    Math.random().toString(36).slice(2, 7)
);
</script>
