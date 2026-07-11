<template>
  <div class="wpruby-ag-view">
    <SettingsCard
      :title="upgradeTitle"
      :description="upgradeIntro"
    >
      <p class="wpruby-ag-upgrade-copy">{{ upgradeCopy }}</p>

      <div class="wpruby-ag-upgrade-grid">
        <div class="wpruby-ag-upgrade-column">
          <h3>{{ liteTitle }}</h3>
          <ul>
            <li v-for="item in liteFeatures" :key="item">{{ item }}</li>
          </ul>
        </div>
        <div class="wpruby-ag-upgrade-column wpruby-ag-upgrade-column--pro">
          <h3>{{ proTitle }}</h3>
          <ul>
            <li v-for="item in proFeatures" :key="item">{{ item }}</li>
          </ul>
        </div>
      </div>

      <p class="wpruby-ag-upgrade-cta">
        <a
          class="wpruby-ag-btn wpruby-ag-btn--primary"
          :href="proUrl"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ ctaLabel }}
        </a>
      </p>
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';

const boot = window.addressGuardAdmin || {};

const proUrl = computed(() => state.meta.pro_url || boot.proUrl || 'https://wpruby.com/plugin/woocommerce-address-guard-pro/');

const upgradeTitle = __('Upgrade to Pro');
const upgradeIntro = __('Address Guard Lite performs local checkout checks only.');
const upgradeCopy = __(
  'Need address autocomplete, provider-powered validation, correction suggestions, Loqate, Google, Mapbox, advanced rules, and order review tools? Upgrade to Address Guard Pro.'
);

const liteTitle = __('Lite (this plugin)');
const proTitle = __('Address Guard Pro');

const liteFeatures = [
  __('Local address checks'),
  __('Missing house number detection'),
  __('PO box detection'),
  __('Parcel locker detection'),
  __('Warn or block checkout'),
];

const proFeatures = [
  __('Google, Mapbox, and Loqate providers'),
  __('Address autocomplete'),
  __('Provider-powered address validation'),
  __('Correction suggestions'),
  __('Advanced rules'),
  __('Order review panel'),
  __('Logs'),
  __('Address tester'),
];

const ctaLabel = __('Learn about Address Guard Pro');
</script>

<style scoped>
.wpruby-ag-upgrade-copy {
  margin: 0 0 1.25rem;
  max-width: 52rem;
}

.wpruby-ag-upgrade-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.wpruby-ag-upgrade-column {
  border: 1px solid #dcdcde;
  border-radius: 6px;
  padding: 1rem 1.25rem;
  background: #fff;
}

.wpruby-ag-upgrade-column--pro {
  border-color: #2271b1;
}

.wpruby-ag-upgrade-column h3 {
  margin: 0 0 0.75rem;
  font-size: 1rem;
}

.wpruby-ag-upgrade-column ul {
  margin: 0;
  padding-left: 1.2rem;
}

.wpruby-ag-upgrade-column li {
  margin-bottom: 0.35rem;
}

.wpruby-ag-upgrade-cta {
  margin: 0;
}
</style>
