<template>
  <div
    class="wpruby-ag-field wpruby-ag-field--multiselect"
    :class="{ 'wpruby-ag-field--error': Boolean(error) }"
  >
    <label v-if="label" :for="inputId" class="wpruby-ag-field__label">{{ label }}</label>

    <div
      ref="rootRef"
      class="wpruby-ag-multiselect"
      :class="{
        'wpruby-ag-multiselect--open': isOpen,
        'wpruby-ag-multiselect--disabled': disabled,
        'wpruby-ag-multiselect--error': Boolean(error),
      }"
    >
      <div
        class="wpruby-ag-multiselect__control"
        @mousedown="onControlMouseDown"
      >
        <div class="wpruby-ag-multiselect__chips">
          <span
            v-for="chip in selectedChips"
            :key="chip.value"
            class="wpruby-ag-multiselect__chip"
            :class="{ 'wpruby-ag-multiselect__chip--unknown': chip.unknown }"
          >
            <span class="wpruby-ag-multiselect__chip-label">{{ chip.label }}</span>
            <button
              type="button"
              class="wpruby-ag-multiselect__chip-remove"
              :aria-label="removeLabel(chip.label)"
              :disabled="disabled"
              tabindex="-1"
              @click.stop="removeValue(chip.value)"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </span>

          <input
            :id="inputId"
            ref="inputRef"
            type="text"
            class="wpruby-ag-multiselect__input"
            :placeholder="inputPlaceholder"
            :disabled="disabled"
            :value="searchQuery"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="isOpen"
            :aria-controls="listboxId"
            :aria-activedescendant="activeOptionId"
            autocomplete="off"
            @input="onSearchInput"
            @focus="openDropdown"
            @keydown="onKeydown"
          />
        </div>

        <div class="wpruby-ag-multiselect__actions">
          <button
            v-if="clearable && modelValue.length && !disabled"
            type="button"
            class="wpruby-ag-multiselect__clear"
            :aria-label="clearAllLabel"
            tabindex="-1"
            @click.stop="clearAll"
          >
            <span aria-hidden="true">&times;</span>
          </button>
          <span class="wpruby-ag-multiselect__arrow" aria-hidden="true"></span>
        </div>
      </div>

      <div
        v-if="isOpen"
        :id="listboxId"
        class="wpruby-ag-multiselect__dropdown"
        role="listbox"
        :aria-label="label || placeholder"
      >
        <div v-if="loading" class="wpruby-ag-multiselect__loading">{{ loadingLabel }}</div>
        <div v-else-if="asyncHint" class="wpruby-ag-multiselect__empty">{{ asyncHint }}</div>
        <div v-else-if="!visibleOptions.length" class="wpruby-ag-multiselect__empty">{{ emptyLabel }}</div>
        <template v-else>
          <button
            v-for="(opt, index) in visibleOptions"
            :id="`${listboxId}-opt-${index}`"
            :key="opt.value"
            type="button"
            class="wpruby-ag-multiselect__option"
            :class="{
              'wpruby-ag-multiselect__option--highlighted': index === highlightedIndex,
              'wpruby-ag-multiselect__option--selected': isSelected(opt.value),
            }"
            role="option"
            :aria-selected="isSelected(opt.value)"
            @mousedown.prevent="selectOption(opt)"
            @mouseenter="highlightedIndex = index"
          >
            {{ opt.label }}
          </button>
          <div
            v-if="truncatedCount > 0"
            class="wpruby-ag-multiselect__truncated"
          >
            {{ truncatedMessage }}
          </div>
        </template>
      </div>
    </div>

    <p v-if="error" class="wpruby-ag-field__error">{{ error }}</p>
    <p v-else-if="helperTextContent" class="wpruby-ag-field__help">{{ helperTextContent }}</p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { __ } from '../api/client.js';

const MAX_VISIBLE_OPTIONS = 100;
const SEARCH_DEBOUNCE_MS = 300;

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Search...' },
  label: { type: String, default: '' },
  helperText: { type: String, default: '' },
  help: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  searchable: { type: Boolean, default: true },
  clearable: { type: Boolean, default: true },
  async: { type: Boolean, default: false },
  minSearchChars: { type: Number, default: 2 },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
});

const emit = defineEmits(['update:modelValue', 'search', 'change']);

const rootRef = ref(null);
const inputRef = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const searchTimer = ref(null);

const instanceId = Math.random().toString(36).slice(2, 9);
const inputId = `wpruby-ag-multiselect-${instanceId}`;
const listboxId = `wpruby-ag-multiselect-list-${instanceId}`;

const emptyLabel = __('No options found');
const loadingLabel = __('Loading…');
const clearAllLabel = __('Clear all selected');
const unknownLabel = __('Unknown value');

const helperTextContent = computed(() => props.helperText || props.help || '');

const inputPlaceholder = computed(() => {
  if (props.modelValue.length) {
    return props.searchable ? '' : props.placeholder;
  }
  return props.placeholder;
});

const normalizedOptions = computed(() =>
  (props.options || []).map((option) => normalizeOption(option))
);

const optionMap = computed(() => {
  const map = new Map();
  normalizedOptions.value.forEach((option) => {
    map.set(option.value, option);
  });
  return map;
});

const selectedSet = computed(() => new Set((props.modelValue || []).map(normalizeValue)));

const selectedChips = computed(() =>
  (props.modelValue || []).map((value) => {
    const normalized = normalizeValue(value);
    const option = optionMap.value.get(normalized);
    if (option) {
      return { value: normalized, label: option.label, unknown: false };
    }
    return {
      value: normalized,
      label: `${unknownLabel}: ${normalized}`,
      unknown: true,
    };
  })
);

const filteredOptions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  const available = normalizedOptions.value.filter(
    (option) => !selectedSet.value.has(option.value)
  );

  if (props.async) {
    return available;
  }

  if (!props.searchable || !query) {
    return available;
  }

  return available.filter((option) => {
    const label = option.label.toLowerCase();
    const code = option.value.toLowerCase();
    return label.includes(query) || code.includes(query);
  });
});

const visibleOptions = computed(() => filteredOptions.value.slice(0, MAX_VISIBLE_OPTIONS));

const truncatedCount = computed(() =>
  Math.max(0, filteredOptions.value.length - MAX_VISIBLE_OPTIONS)
);

const truncatedMessage = computed(() =>
  __('Refine your search to see %d more options.').replace('%d', String(truncatedCount.value))
);

const asyncHint = computed(() => {
  if (!props.async || props.loading) {
    return '';
  }
  const query = searchQuery.value.trim();
  if (query.length < props.minSearchChars) {
    return __('Type at least %d characters to search.').replace('%d', String(props.minSearchChars));
  }
  return '';
});

const activeOptionId = computed(() => {
  if (!isOpen.value || highlightedIndex.value < 0) {
    return undefined;
  }
  return `${listboxId}-opt-${highlightedIndex.value}`;
});

watch(
  () => props.options,
  () => {
    if (highlightedIndex.value >= visibleOptions.value.length) {
      highlightedIndex.value = visibleOptions.value.length - 1;
    }
  }
);

watch(visibleOptions, (options) => {
  if (!isOpen.value) {
    return;
  }
  if (!options.length) {
    highlightedIndex.value = -1;
    return;
  }
  if (highlightedIndex.value < 0 || highlightedIndex.value >= options.length) {
    highlightedIndex.value = 0;
  }
});

function normalizeValue(value) {
  return String(value);
}

function normalizeOption(option) {
  if (option === null || option === undefined) {
    return { value: '', label: '' };
  }
  if (typeof option === 'string' || typeof option === 'number') {
    const text = String(option);
    return { value: text, label: text };
  }
  const value = option[props.optionValue];
  const label = option[props.optionLabel] ?? value;
  return {
    value: normalizeValue(value),
    label: String(label ?? ''),
  };
}

function isSelected(value) {
  return selectedSet.value.has(normalizeValue(value));
}

function removeLabel(label) {
  return __('Remove %s').replace('%s', label);
}

function updateValues(values) {
  emit('update:modelValue', values);
  emit('change', values);
}

function openDropdown() {
  if (props.disabled) {
    return;
  }
  isOpen.value = true;
  if (highlightedIndex.value < 0 && visibleOptions.value.length) {
    highlightedIndex.value = 0;
  }
}

function closeDropdown() {
  isOpen.value = false;
  highlightedIndex.value = -1;
}

function onControlMouseDown(event) {
  if (props.disabled) {
    return;
  }
  if (event.target.closest('.wpruby-ag-multiselect__chip-remove, .wpruby-ag-multiselect__clear')) {
    return;
  }
  event.preventDefault();
  inputRef.value?.focus();
  openDropdown();
}

function onSearchInput(event) {
  searchQuery.value = event.target.value;
  openDropdown();
  highlightedIndex.value = 0;
  scheduleSearchEmit();
}

function scheduleSearchEmit() {
  if (!props.async) {
    return;
  }
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }
  searchTimer.value = setTimeout(() => {
    const query = searchQuery.value.trim();
    if (query.length >= props.minSearchChars) {
      emit('search', query);
    }
  }, SEARCH_DEBOUNCE_MS);
}

function selectOption(option) {
  if (!option || props.disabled) {
    return;
  }
  const value = normalizeValue(option.value);
  if (selectedSet.value.has(value)) {
    return;
  }
  updateValues([...(props.modelValue || []), value]);
  searchQuery.value = '';
  highlightedIndex.value = 0;
  inputRef.value?.focus();
}

function removeValue(value) {
  if (props.disabled) {
    return;
  }
  const normalized = normalizeValue(value);
  updateValues((props.modelValue || []).filter((item) => normalizeValue(item) !== normalized));
}

function clearAll() {
  if (props.disabled) {
    return;
  }
  updateValues([]);
  searchQuery.value = '';
  inputRef.value?.focus();
}

function onKeydown(event) {
  if (props.disabled) {
    return;
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    closeDropdown();
    inputRef.value?.blur();
    return;
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    openDropdown();
    if (!visibleOptions.value.length) {
      return;
    }
    highlightedIndex.value = Math.min(
      highlightedIndex.value + 1,
      visibleOptions.value.length - 1
    );
    return;
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    openDropdown();
    if (!visibleOptions.value.length) {
      return;
    }
    highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    return;
  }

  if (event.key === 'Enter') {
    if (!isOpen.value) {
      openDropdown();
      return;
    }
    event.preventDefault();
    const option = visibleOptions.value[highlightedIndex.value];
    if (option) {
      selectOption(option);
    }
    return;
  }

  if (event.key === 'Backspace' && !searchQuery.value && props.modelValue.length) {
    const lastValue = props.modelValue[props.modelValue.length - 1];
    removeValue(lastValue);
  }
}

function onDocumentMouseDown(event) {
  if (!rootRef.value?.contains(event.target)) {
    closeDropdown();
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onDocumentMouseDown);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentMouseDown);
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }
});
</script>
