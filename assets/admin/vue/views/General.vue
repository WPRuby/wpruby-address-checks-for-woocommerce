<template>
  <div class="agl-view">
    <header class="agl-view-header">
      <h2 class="agl-view-header__title">{{ generalTitle }}</h2>
      <p class="agl-view-header__desc">{{ generalDesc }}</p>
    </header>

    <SettingsCard
      :title="statusTitle"
      :description="statusDesc"
    >
      <ToggleField
        v-model="settings.plugin_enabled"
        :label="enableLabel"
        :help="enableHelp"
      />
    </SettingsCard>

    <SettingsCard
      :title="behaviorTitle"
      :description="behaviorQuestion"
    >
      <RadioCardField
        v-model="settings.validation_mode"
        :options="behaviorOptions"
        :disabled="!settings.plugin_enabled"
        :legend="behaviorQuestion"
      />
    </SettingsCard>

    <SettingsCard
      :title="applyTitle"
      :description="applyDesc"
    >
      <ToggleField
        v-model="settings.validate_shipping_address"
        :label="shippingLabel"
        :help="shippingHelp"
        :disabled="!settings.plugin_enabled"
      />
      <ToggleField
        v-model="settings.validate_billing_address"
        :label="billingLabel"
        :help="billingHelp"
        :disabled="!settings.plugin_enabled"
      />
    </SettingsCard>

    <SettingsCard
      :title="notesTitle"
      :description="notesDesc"
    >
      <ToggleField
        v-model="settings.order_add_validation_notes"
        :label="notesLabel"
        :help="notesHelp"
        :disabled="!settings.plugin_enabled"
      />
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import ToggleField from '../components/ToggleField.vue';
import RadioCardField from '../components/RadioCardField.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';

const settings = computed(() => state.settings);

const generalTitle = __('General');
const generalDesc = __('Enable Address Guard and choose how checkout should respond to local address issues.');

const statusTitle = __('Plugin status');
const statusDesc = __('Turn local address checks on or off for checkout.');
const enableLabel = __('Enable Address Guard');
const enableHelp = __('When enabled, configured checks run during checkout.');

const behaviorTitle = __('Checkout behavior');
const behaviorQuestion = __('How should checkout respond?');
const warnLabel = __('Warn customer');
const warnDesc = __('Show a notice but allow checkout.');
const blockLabel = __('Block checkout');
const blockDesc = __('Stop the customer until the issue is fixed.');

const behaviorOptions = [
  { value: 'warn', label: warnLabel, description: warnDesc },
  { value: 'block', label: blockLabel, description: blockDesc },
];

const applyTitle = __('Apply checks to');
const applyDesc = __('Choose which address types are validated at checkout.');
const shippingLabel = __('Shipping address');
const shippingHelp = __('Validate the shipping address at checkout.');
const billingLabel = __('Billing address');
const billingHelp = __('Validate the billing address at checkout.');

const notesTitle = __('Order notes');
const notesDesc = __('Automatically record check results on the order.');
const notesLabel = __('Add order notes when a check triggers');
const notesHelp = __('Adds a private order note such as “Address Guard: Missing house number detected.”');
</script>
