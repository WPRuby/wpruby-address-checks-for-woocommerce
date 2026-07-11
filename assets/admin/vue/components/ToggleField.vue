<template>
  <div class="agl-field-row" :class="{ 'agl-field-row--disabled': disabled }">
    <div class="agl-field-row__content">
      <span class="agl-field-row__label">{{ label }}</span>
      <p v-if="help" class="agl-field-row__help">{{ help }}</p>
    </div>
    <label class="agl-switch">
      <input
        type="checkbox"
        role="switch"
        :checked="modelValue"
        :disabled="disabled"
        :aria-checked="modelValue ? 'true' : 'false'"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <span class="agl-switch__track" aria-hidden="true"></span>
      <span class="screen-reader-text">{{ label }}</span>
    </label>
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
