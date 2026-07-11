<template>
  <div class="wpruby-ag-field">
    <label class="wpruby-ag-toggle">
      <input
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <span class="wpruby-ag-toggle__track" aria-hidden="true"></span>
      <span class="wpruby-ag-toggle__label">{{ label }}</span>
    </label>
    <p v-if="help" class="wpruby-ag-field__help">{{ help }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  help: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>
