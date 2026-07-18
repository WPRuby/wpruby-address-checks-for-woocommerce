<template>
  <div class="agl-view">
    <header class="agl-view-header">
      <h2 class="agl-view-header__title">{{ pageTitle }}</h2>
      <p class="agl-view-header__desc">{{ pageDesc }}</p>
    </header>

    <SettingsCard
      :title="cardTitle"
      :description="cardDesc"
    >
      <ToggleField
        v-model="settings.autocomplete_enabled"
        :label="enableLabel"
        :help="enableHelp"
        :disabled="!settings.plugin_enabled"
      />

      <TextField
        v-model="settings.google_api_key"
        type="password"
        :label="apiKeyLabel"
        :help="apiKeyHelp"
        :placeholder="apiKeyPlaceholder"
        :disabled="!settings.plugin_enabled || !settings.autocomplete_enabled"
      />

      <MultiSelectField
        v-model="settings.autocomplete_countries"
        :options="countryOptions"
        :label="countriesLabel"
        :help="countriesHelp"
        :placeholder="countriesPlaceholder"
        :disabled="!settings.plugin_enabled || !settings.autocomplete_enabled"
      />

      <div class="agl-autocomplete-actions">
        <button
          type="button"
          class="agl-button"
          :disabled="testing || !settings.plugin_enabled"
          @click="runTest"
        >
          {{ testing ? testingLabel : testLabel }}
        </button>
        <p v-if="testMessage" class="agl-autocomplete-test" :class="testMessageClass">
          {{ testMessage }}
        </p>
      </div>
    </SettingsCard>

    <Callout variant="info">
      <strong>{{ setupTitle }}</strong>
      <p>{{ setupBody }}</p>
      <p v-if="docsUrl">
        <a :href="docsUrl" target="_blank" rel="noopener noreferrer">{{ docsLabel }}</a>
      </p>
    </Callout>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import ToggleField from '../components/ToggleField.vue';
import TextField from '../components/TextField.vue';
import MultiSelectField from '../components/MultiSelectField.vue';
import Callout from '../components/Callout.vue';
import { state } from '../store.js';
import { api, ApiError, __ } from '../api/client.js';
import { useCountryOptions } from '../utils/countries.js';

const settings = computed(() => state.settings);
const { countryOptions } = useCountryOptions();

const testing = ref(false);
const testMessage = ref('');
const testSuccess = ref(null);

const pageTitle = __('Autocomplete');
const pageDesc = __(
  'Help customers enter addresses faster with Google Places Autocomplete and catch common checkout address issues such as missing house numbers, PO boxes, and parcel locker addresses.'
);

const cardTitle = __('Google Places Autocomplete');
const cardDesc = __('Show Google-powered address suggestions while customers type their checkout address.');

const enableLabel = __('Enable address autocomplete at checkout');
const enableHelp = __('Show Google-powered address suggestions while customers type their checkout address.');

const apiKeyLabel = __('Google API key');
const apiKeyHelp = __('Use your own Google Maps Platform API key. Address Guard does not include bundled API usage.');
const apiKeyPlaceholder = '••••••••';

const countriesLabel = __('Autocomplete countries');
const countriesHelp = __('Limit address suggestions to selected countries. Leave empty to use the customer’s selected checkout country.');
const countriesPlaceholder = __('Search countries…');

const testLabel = __('Test Google Autocomplete');
const testingLabel = __('Testing…');

const setupTitle = __('Google setup required');
const setupBody = __('Enable the Places API in your Google Cloud project. Your store uses your own Google Maps Platform billing account.');
const docsLabel = __('View setup documentation');

const docsUrl = computed(() => state.meta.docs_url || '');

const testMessageClass = computed(() => ({
  'agl-autocomplete-test--success': testSuccess.value === true,
  'agl-autocomplete-test--error': testSuccess.value === false,
}));

async function runTest() {
  testing.value = true;
  testMessage.value = '';
  testSuccess.value = null;

  try {
    const result = await api.testGoogleAutocomplete();
    testSuccess.value = Boolean(result?.success);
    testMessage.value =
      result?.message ||
      (testSuccess.value
        ? __('Google Places Autocomplete is connected.')
        : __('Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.'));
  } catch (error) {
    testSuccess.value = false;
    testMessage.value =
      error instanceof ApiError
        ? error.message
        : __('Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.');
  } finally {
    testing.value = false;
  }
}
</script>

<style scoped>
.agl-autocomplete-actions {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.75rem;
}

.agl-autocomplete-test {
  margin: 0;
  font-size: 0.875rem;
}

.agl-autocomplete-test--success {
  color: #008a20;
}

.agl-autocomplete-test--error {
  color: #b32d2e;
}
</style>
