<template>
  <div class="wpruby-ag-view">
    <SettingsCard
      :title="generalTitle"
      :description="generalDesc"
    >
      <ToggleField
        v-model="settings.plugin_enabled"
        :label="enableLabel"
        :help="enableHelp"
      />

      <div class="wpruby-ag-field">
        <span class="wpruby-ag-field__label">{{ behaviorLabel }}</span>
        <div class="wpruby-ag-radio-group">
          <label class="wpruby-ag-radio">
            <input
              v-model="settings.validation_mode"
              type="radio"
              value="warn"
              :disabled="!settings.plugin_enabled"
            />
            <span>{{ warnLabel }}</span>
          </label>
          <label class="wpruby-ag-radio">
            <input
              v-model="settings.validation_mode"
              type="radio"
              value="block"
              :disabled="!settings.plugin_enabled"
            />
            <span>{{ blockLabel }}</span>
          </label>
        </div>
        <p class="wpruby-ag-field__help">{{ behaviorHelp }}</p>
      </div>

      <div class="wpruby-ag-field">
        <span class="wpruby-ag-field__label">{{ applyLabel }}</span>
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
      </div>

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
import { state } from '../store.js';
import { __ } from '../api/client.js';

const settings = computed(() => state.settings);

const generalTitle = __('General');
const generalDesc = __('Enable Address Guard and choose how checkout should respond to local address issues.');
const enableLabel = __('Enable Address Guard');
const enableHelp = __('Turn local address checks on or off for checkout.');
const behaviorLabel = __('Checkout behavior');
const warnLabel = __('Warn customer');
const blockLabel = __('Block checkout');
const behaviorHelp = __('Warn shows a notice but allows checkout. Block prevents placing the order.');
const applyLabel = __('Apply to');
const shippingLabel = __('Shipping address');
const shippingHelp = __('Validate the shipping address at checkout.');
const billingLabel = __('Billing address');
const billingHelp = __('Validate the billing address at checkout.');
const notesLabel = __('Add order notes when a check triggers');
const notesHelp = __('Adds a private order note such as “Address Guard: Missing house number detected.”');
</script>
