<template>
  <div class="agl-view agl-view--upgrade">
    <header class="agl-view-header">
      <h2 class="agl-view-header__title">{{ pageTitle }}</h2>
      <p class="agl-view-header__desc">{{ pageIntro }}</p>
    </header>

    <SettingsCard
      :title="heroTitle"
      :description="heroDesc"
    >
      <p class="agl-upgrade-positioning">{{ freePositioning }}</p>
      <p class="agl-upgrade-positioning agl-upgrade-positioning--pro">{{ proPositioning }}</p>

      <div class="agl-upgrade-actions">
        <a
          class="agl-button agl-button--primary"
          :href="proUrlUpgradeTab"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ upgradeLabel }}
        </a>
        <a
          class="agl-button"
          :href="docsUrl"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ docsLabel }}
        </a>
      </div>
    </SettingsCard>

    <div class="agl-upgrade-compare">
      <SettingsCard :title="freeTitle" compact>
        <ul class="agl-upgrade-list" :aria-label="freeTitle">
          <li v-for="feature in freeFeatures" :key="feature" class="agl-upgrade-list__item">
            <span class="agl-upgrade-list__icon" aria-hidden="true">
              <svg viewBox="0 0 20 20" width="16" height="16" fill="none">
                <path
                  d="M16.25 5.75 8.5 14.25 3.75 9.75"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
            <span>{{ feature }}</span>
          </li>
        </ul>
      </SettingsCard>

      <SettingsCard :title="proTitle" compact class="agl-upgrade-card--pro">
        <ul class="agl-upgrade-list" :aria-label="proTitle">
          <li v-for="feature in proFeatures" :key="feature" class="agl-upgrade-list__item">
            <span class="agl-upgrade-list__icon agl-upgrade-list__icon--pro" aria-hidden="true">
              <svg viewBox="0 0 20 20" width="16" height="16" fill="none">
                <path
                  d="M16.25 5.75 8.5 14.25 3.75 9.75"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
            <span>{{ feature }}</span>
          </li>
        </ul>
      </SettingsCard>
    </div>

    <SettingsCard :title="gridTitle" :description="gridDesc">
      <div class="agl-upgrade-grid">
        <div
          v-for="item in featureGrid"
          :key="item"
          class="agl-upgrade-grid__item"
        >
          <span class="agl-upgrade-grid__icon" aria-hidden="true">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="none">
              <path
                d="M16.25 5.75 8.5 14.25 3.75 9.75"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <span>{{ item }}</span>
        </div>
      </div>

      <div class="agl-upgrade-actions">
        <a
          class="agl-button agl-button--primary"
          :href="proUrlFeatureComparison"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ upgradeLabel }}
        </a>
        <a
          class="agl-button"
          :href="docsUrl"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ docsLabel }}
        </a>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';
import { getProUrl } from '../utils/proUrls.js';

const pageTitle = __('Upgrade to Pro');
const pageIntro = __(
  'Need deeper address protection? Upgrade to Pro for provider-powered validation, correction suggestions, advanced rules, address testing, logs, and order review tools.'
);

const heroTitle = __('Go beyond autocomplete and local checks');
const heroDesc = __(
  'The free plugin helps customers enter addresses and catches common checkout issues. Pro adds provider-powered address validation, correction suggestions, advanced rule automation, and order-level review data for fulfillment teams.'
);

const freePositioning = __(
  'WPRuby Address Checks for WooCommerce helps customers enter addresses faster with Google Places Autocomplete and catches common checkout address issues such as missing house numbers, PO boxes, and parcel locker addresses.'
);
const proPositioning = __(
  'Upgrade to Pro for provider-powered address validation, correction suggestions, advanced rules, an address tester, logs, and order review tools.'
);

const freeTitle = __('Free');
const proTitle = __('Pro');
const gridTitle = __('What Pro adds');
const gridDesc = __('Provider-powered validation and advanced checkout tools beyond local checks.');

const upgradeLabel = __('Upgrade to Pro');
const docsLabel = __('View Pro Features');

const freeFeatures = [
  __('Google Places Autocomplete'),
  __('Missing house number detection'),
  __('PO box detection'),
  __('Parcel locker / Packstation detection'),
  __('Warn or block checkout'),
  __('Checkout Blocks support'),
  __('Classic checkout support'),
  __('Merchant-owned Google API key'),
];

const proFeatures = [
  __('Google Address Validation'),
  __('Loqate Address Verify'),
  __('Mapbox Address Autofill'),
  __('Loqate Address Capture'),
  __('Correction suggestions'),
  __('Advanced checkout rules'),
  __('Rule presets'),
  __('Address tester'),
  __('Order review panel'),
  __('Logs and diagnostics'),
  __('Provider error handling'),
  __('Multi-provider setup'),
  __('Priority support'),
];

const featureGrid = [
  __('Google Address Validation'),
  __('Loqate Verify'),
  __('Mapbox / Loqate autocomplete'),
  __('Correction suggestions'),
  __('Advanced checkout rules'),
  __('Address tester'),
  __('Order review panel'),
  __('Logs'),
];

const proUrlUpgradeTab = computed(
  () => state.meta.pro_url || getProUrl('upgrade_tab')
);
const proUrlFeatureComparison = computed(
  () => state.meta.pro_url_feature_comparison || getProUrl('feature_comparison')
);
const docsUrl = computed(
  () =>
    state.meta.docs_url ||
    'https://wpruby.com/knowledgebase_category/woocommerce-address-guard-pro/?utm_source=wpruby-address-checks&utm_medium=plugin&utm_campaign=upgrade-to-pro&utm_content=settings-view-pro-features'
);
</script>
