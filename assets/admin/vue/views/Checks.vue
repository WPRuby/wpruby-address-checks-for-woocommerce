<template>
  <div class="agl-view">
    <header class="agl-view-header">
      <h2 class="agl-view-header__title">{{ checksTitle }}</h2>
      <p class="agl-view-header__desc">{{ checksDesc }}</p>
    </header>

    <SettingsCard
      :title="checksCardTitle"
      :description="checksCardDesc"
    >
      <ToggleField
        v-for="check in checks"
        :key="check.key"
        v-model="settings[check.key]"
        :label="check.label"
        :help="check.help"
        :disabled="!settings.plugin_enabled"
      />
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import ToggleField from '../components/ToggleField.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';

const settings = computed(() => state.settings);

const checksTitle = __('Checks');
const checksDesc = __('Choose which local address checks run at checkout. These checks do not call external APIs.');
const checksCardTitle = __('Local address checks');
const checksCardDesc = __('Enable the checks that should run when customers enter their address at checkout.');

const checks = [
  {
    key: 'check_missing_house_number',
    label: __('Missing house number'),
    help: __('Detect street addresses without a plausible house or building number (US, CA, GB, AU, NZ, IE).'),
  },
  {
    key: 'check_po_box',
    label: __('PO box address'),
    help: __('Detect common PO box patterns such as PO Box, Postfach, and Boîte postale.'),
  },
  {
    key: 'check_parcel_locker',
    label: __('Parcel locker / Packstation'),
    help: __('Detect parcel locker addresses such as Packstation, Amazon Locker, and Paketshop.'),
  },
  {
    key: 'check_postcode_format',
    label: __('Basic postcode format'),
    help: __('Check postcode format for US, CA, GB, and AU when a postcode is provided.'),
  },
];
</script>
