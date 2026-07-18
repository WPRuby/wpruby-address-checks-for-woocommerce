import { reactive, computed } from 'vue';
import { api, ApiError, __ } from './api/client.js';

const boot = window.addressGuardAdmin || {};

export const state = reactive({
  ready: false,
  loadError: '',
  settings: null,
  meta: blankMeta(),
  savedSnapshot: '',
  savingSettings: false,
  notice: null,
});

let noticeTimer = null;

const BOOL_KEYS = [
  'plugin_enabled',
  'validate_shipping_address',
  'validate_billing_address',
  'autocomplete_enabled',
  'check_missing_house_number',
  'check_po_box',
  'check_parcel_locker',
  'check_postcode_format',
  'order_add_validation_notes',
];

export function blankMeta() {
  return {
    checkout_blocks: false,
    checkout_classic: true,
    checkout_detected: 'classic',
    checkout_detected_label: '',
    supports_blocks: true,
    supports_classic: true,
    country_options: boot.countryOptions || [],
    docs_url: '',
  };
}

export function normalizeSettings(raw = {}) {
  const settings = { ...raw };

  BOOL_KEYS.forEach((key) => {
    settings[key] = toBool(settings[key]);
  });

  if (!settings.messages || typeof settings.messages !== 'object') {
    settings.messages = {};
  }

  const bootMessages = boot.defaultMessages || {};
  settings.messages = {
    ...bootMessages,
    ...settings.messages,
  };

  if (!['warn', 'block'].includes(settings.validation_mode)) {
    settings.validation_mode = 'warn';
  }

  if (!Array.isArray(settings.autocomplete_countries)) {
    settings.autocomplete_countries = [];
  } else {
    settings.autocomplete_countries = settings.autocomplete_countries.map((code) =>
      String(code || '').toUpperCase()
    );
  }

  if (typeof settings.google_api_key !== 'string') {
    settings.google_api_key = '';
  }

  return settings;
}

export function serializeSettings(settings) {
  const payload = { ...settings, messages: { ...settings.messages } };

  BOOL_KEYS.forEach((key) => {
    payload[key] = toYesNo(payload[key]);
  });

  return payload;
}

function toBool(value) {
  if (typeof value === 'boolean') {
    return value;
  }
  if (typeof value === 'number') {
    return value !== 0;
  }
  const normalized = String(value || '').toLowerCase();
  return ['yes', 'true', '1', 'on'].includes(normalized);
}

function toYesNo(value) {
  return value ? 'yes' : 'no';
}

function snapshot() {
  state.savedSnapshot = JSON.stringify(state.settings);
}

export const isDirty = computed(() => {
  if (!state.settings) {
    return false;
  }
  return JSON.stringify(state.settings) !== state.savedSnapshot;
});

export function setNotice(type, message) {
  state.notice = { type, message };
  if (noticeTimer) {
    clearTimeout(noticeTimer);
  }
  if (type === 'success') {
    noticeTimer = setTimeout(() => {
      state.notice = null;
    }, 4000);
  }
}

export function clearNotice() {
  state.notice = null;
}

function applyPayload(payload) {
  if (payload.settings) {
    const normalized = normalizeSettings(payload.settings);
    Object.keys(state.settings).forEach((key) => {
      if (!(key in normalized)) {
        delete state.settings[key];
      }
    });
    Object.assign(state.settings, normalized);
  }
  if (payload.meta) {
    Object.assign(state.meta, payload.meta);
  }
}

export async function loadSettings() {
  state.ready = false;
  state.loadError = '';

  try {
    const payload = await api.getSettings();
    state.settings = normalizeSettings(payload.settings || payload);
    state.meta = { ...blankMeta(), ...(payload.meta || boot.meta || {}) };
    snapshot();
    state.ready = true;
  } catch (error) {
    state.loadError =
      error instanceof ApiError
        ? error.message
        : __('Failed to load Address Guard settings.');
  }
}

export async function saveSettings() {
  if (state.savingSettings || !state.settings) {
    return;
  }

  state.savingSettings = true;
  clearNotice();

  try {
    const response = await api.saveSettings(serializeSettings(state.settings));
    applyPayload(response);
    snapshot();
    setNotice('success', response.message || __('Settings saved.'));
  } catch (error) {
    setNotice(
      'error',
      error instanceof ApiError ? error.message : __('Could not save settings.')
    );
  } finally {
    state.savingSettings = false;
  }
}

export function validationModeLabel(mode) {
  switch (mode) {
    case 'block':
      return __('Block checkout');
    case 'warn':
      return __('Warn customer');
    default:
      return __('Warn customer');
  }
}
