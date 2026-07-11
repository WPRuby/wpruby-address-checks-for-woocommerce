<template>
  <article
    class="wpruby-ag-message-card"
    :class="{ 'wpruby-ag-message-card--inserted': showInsertFlash }"
  >
    <header class="wpruby-ag-message-card__header">
      <h4 class="wpruby-ag-message-card__title">{{ title }}</h4>
      <p v-if="description" class="wpruby-ag-message-card__description">{{ description }}</p>
    </header>

    <TextareaField
      ref="textareaFieldRef"
      class="wpruby-ag-message-card__textarea"
      :model-value="modelValue"
      :label="templateLabel"
      :rows="rows"
      @update:model-value="$emit('update:modelValue', $event)"
    />

    <div class="wpruby-ag-message-card__insert">
      <span class="wpruby-ag-message-card__insert-label">{{ insertLabel }}</span>
      <div class="wpruby-ag-message-card__placeholder-row">
        <button
          v-for="item in recommendedItems"
          :key="item.token"
          type="button"
          class="wpruby-ag-message-card__placeholder-chip"
          :title="item.label"
          :aria-label="insertChipLabel(item.token)"
          @mousedown.prevent
          @click="insertPlaceholder(item.token)"
        >
          {{ item.token }}
        </button>
      </div>
      <div
        v-if="moreItems.length"
        ref="moreRootRef"
        class="wpruby-ag-message-card__more"
      >
        <button
          type="button"
          class="wpruby-ag-message-card__more-toggle"
          :aria-expanded="moreOpen ? 'true' : 'false'"
          aria-haspopup="listbox"
          :aria-controls="moreListId"
          @click="toggleMore"
        >
          {{ moreLabel }}
        </button>
        <div
          v-if="moreOpen"
          :id="moreListId"
          class="wpruby-ag-message-card__more-dropdown"
          role="listbox"
          :aria-label="moreDropdownLabel"
        >
          <input
            v-if="moreItems.length > 4"
            ref="moreSearchRef"
            v-model="moreSearch"
            type="search"
            class="wpruby-ag-message-card__more-search"
            :placeholder="searchPlaceholder"
            @keydown.stop
          />
          <button
            v-for="(item, index) in filteredMoreItems"
            :key="item.token"
            type="button"
            class="wpruby-ag-message-card__more-option"
            role="option"
            :class="{ 'wpruby-ag-message-card__more-option--highlighted': index === highlightedIndex }"
            :aria-selected="index === highlightedIndex ? 'true' : 'false'"
            @mousedown.prevent
            @click="selectMoreItem(item)"
            @mouseenter="highlightedIndex = index"
          >
            <span class="wpruby-ag-message-card__more-token">{{ item.token }}</span>
            <span class="wpruby-ag-message-card__more-meaning">{{ item.label }}</span>
          </button>
          <p
            v-if="!filteredMoreItems.length"
            class="wpruby-ag-message-card__more-empty"
          >
            {{ noResultsLabel }}
          </p>
        </div>
      </div>
    </div>

    <div class="wpruby-ag-message-card__preview" aria-live="polite">
      <span class="wpruby-ag-message-card__preview-label">{{ previewLabel }}</span>
      <p class="wpruby-ag-message-card__preview-text">{{ previewText }}</p>
    </div>

    <div v-if="defaultValue" class="wpruby-ag-message-card__actions">
      <button
        type="button"
        class="wpruby-ag-message-card__reset"
        :disabled="modelValue === defaultValue"
        @click="resetToDefault"
      >
        {{ resetLabel }}
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import TextareaField from './TextareaField.vue';
import { __ } from '../api/client.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  rows: { type: Number, default: 3 },
  fieldKey: { type: String, required: true },
  sampleContext: { type: Object, required: true },
  allPlaceholders: { type: Array, required: true },
  recommendedPlaceholders: { type: Array, default: () => [] },
  defaultValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const textareaFieldRef = ref(null);
const moreRootRef = ref(null);
const moreSearchRef = ref(null);
const showInsertFlash = ref(false);
const moreOpen = ref(false);
const moreSearch = ref('');
const highlightedIndex = ref(0);

const instanceId = Math.random().toString(36).slice(2, 9);
const moreListId = `wpruby-ag-message-more-${instanceId}`;

let flashTimer = null;

const templateLabel = __('Message template');
const insertLabel = __('Insert:');
const previewLabel = __('Preview');
const moreLabel = __('More placeholders ▼');
const moreDropdownLabel = __('All placeholders');
const searchPlaceholder = __('Search placeholders…');
const noResultsLabel = __('No placeholders match your search.');
const resetLabel = __('Reset to default');
const resetConfirmLabel = __('Reset this message to the default template?');

const placeholderMap = computed(() => {
  const map = new Map();
  props.allPlaceholders.forEach((item) => {
    map.set(item.token, item);
  });
  return map;
});

const recommendedItems = computed(() =>
  props.recommendedPlaceholders
    .map((token) => placeholderMap.value.get(token))
    .filter(Boolean)
);

const moreItems = computed(() => {
  const recommended = new Set(props.recommendedPlaceholders);
  return props.allPlaceholders.filter((item) => !recommended.has(item.token));
});

const filteredMoreItems = computed(() => {
  const query = moreSearch.value.trim().toLowerCase();
  if (!query) {
    return moreItems.value;
  }

  return moreItems.value.filter(
    (item) =>
      item.token.toLowerCase().includes(query) ||
      item.label.toLowerCase().includes(query)
  );
});

const previewText = computed(() => {
  const template = props.modelValue || '';
  if (!template) {
    return __('(empty message)');
  }

  let preview = template;
  Object.entries(props.sampleContext).forEach(([placeholder, value]) => {
    preview = preview.split('{' + placeholder + '}').join(value);
  });

  return preview;
});

function insertChipLabel(token) {
  return `${__('Insert placeholder')} ${token} ${__('into')} ${props.title} ${__('message')}`;
}

function flashInserted() {
  showInsertFlash.value = true;
  clearTimeout(flashTimer);
  flashTimer = setTimeout(() => {
    showInsertFlash.value = false;
  }, 700);
}

function insertPlaceholder(token) {
  const inserted = textareaFieldRef.value?.insertAtCursor(token);
  if (inserted) {
    flashInserted();
    closeMore();
  }
  return inserted;
}

function resetToDefault() {
  if (props.modelValue === props.defaultValue) {
    return;
  }

  if (props.modelValue && props.modelValue !== props.defaultValue) {
    if (!window.confirm(resetConfirmLabel)) {
      return;
    }
  }

  emit('update:modelValue', props.defaultValue);
}

function toggleMore() {
  if (moreOpen.value) {
    closeMore();
    return;
  }

  moreOpen.value = true;
  moreSearch.value = '';
  highlightedIndex.value = 0;
  nextTick(() => {
    moreSearchRef.value?.focus();
  });
}

function closeMore() {
  moreOpen.value = false;
  moreSearch.value = '';
  highlightedIndex.value = 0;
}

function selectMoreItem(item) {
  insertPlaceholder(item.token);
}

function onDocumentClick(event) {
  if (!moreOpen.value) {
    return;
  }

  if (!moreRootRef.value?.contains(event.target)) {
    closeMore();
  }
}

function onDocumentKeydown(event) {
  if (!moreOpen.value) {
    return;
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    closeMore();
    return;
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    const max = filteredMoreItems.value.length - 1;
    highlightedIndex.value = Math.min(highlightedIndex.value + 1, Math.max(max, 0));
    return;
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    return;
  }

  if (event.key === 'Enter' && filteredMoreItems.value.length) {
    event.preventDefault();
    selectMoreItem(filteredMoreItems.value[highlightedIndex.value]);
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  document.addEventListener('keydown', onDocumentKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  document.removeEventListener('keydown', onDocumentKeydown);
  clearTimeout(flashTimer);
});
</script>
