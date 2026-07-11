<template>
  <div class="wpruby-ag-view wpruby-ag-messages">
    <SettingsCard
      :title="messagesTitle"
      :description="messagesDesc"
    >
      <MessageTemplateField
        v-for="field in messageFields"
        :key="field.key"
        v-model="settings.messages[field.key]"
        :title="field.label"
        :description="field.help"
        :rows="field.rows || 3"
        :field-key="field.key"
        :sample-context="sampleContext"
        :all-placeholders="placeholderItems"
        :recommended-placeholders="field.recommendedPlaceholders"
        :default-value="defaultMessages[field.key] || ''"
      />
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SettingsCard from '../components/SettingsCard.vue';
import MessageTemplateField from '../components/MessageTemplateField.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';

const boot = window.addressGuardAdmin || {};
const settings = computed(() => state.settings);
const defaultMessages = boot.defaultMessages || {};

const sampleContext = {
  address_type: __('Shipping address'),
  original_address: '123 Main St, Springfield, IL 62701, US',
  field: __('Street address'),
  country: 'US',
  postcode: '62701',
  city: 'Springfield',
};

const placeholderItems = [
  { token: '{address_type}', label: __('Billing or shipping address label') },
  { token: '{original_address}', label: __('Customer-entered address on one line') },
  { token: '{field}', label: __('Relevant address field name') },
  { token: '{country}', label: __('Country code') },
  { token: '{postcode}', label: __('Postcode / ZIP') },
  { token: '{city}', label: __('City') },
];

const messageFields = [
  {
    key: 'missing_house_number',
    label: __('Missing house number'),
    help: __('Shown when a house or building number appears to be missing.'),
    recommendedPlaceholders: ['{address_type}', '{field}'],
  },
  {
    key: 'po_box_blocked',
    label: __('PO box not allowed'),
    help: __('Shown when a PO box is detected.'),
    rows: 2,
    recommendedPlaceholders: ['{address_type}'],
  },
  {
    key: 'locker_blocked',
    label: __('Parcel locker not allowed'),
    help: __('Shown when a parcel locker address is detected.'),
    rows: 2,
    recommendedPlaceholders: ['{address_type}'],
  },
  {
    key: 'country_postcode_mismatch',
    label: __('Country/postcode mismatch'),
    help: __('Shown when the postcode format does not match the selected country.'),
    rows: 2,
    recommendedPlaceholders: ['{postcode}', '{country}'],
  },
  {
    key: 'validation_blocked',
    label: __('Checkout blocked'),
    help: __('Generic message when checkout is blocked.'),
    recommendedPlaceholders: ['{address_type}'],
  },
  {
    key: 'validation_warning',
    label: __('Checkout warning'),
    help: __('Generic message when checkout proceeds with a warning.'),
    recommendedPlaceholders: ['{address_type}'],
  },
];

const messagesTitle = __('Messages');
const messagesDesc = __('Customize the text shown when local address checks trigger at checkout.');
</script>
