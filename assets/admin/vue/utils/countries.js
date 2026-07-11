import { computed } from 'vue';
import { __ } from '../api/client.js';
import { state } from '../store.js';

const boot = window.wpRubyAddressGuard || {};

export function countryOptionsSource() {
  return state.meta?.country_options || boot.countryOptions || [];
}

export function useCountryOptions() {
  const countryOptions = computed(() => countryOptionsSource());

  const hasCountryOptions = computed(() => countryOptions.value.length > 0);

  function buildCountrySelectOptions(currentValue = '', autoLabel = '') {
    const options = [
      {
        value: '',
        label: autoLabel || __('Auto / Not set'),
      },
      ...countryOptions.value,
    ];

    const current = String(currentValue || '').toUpperCase();
    if (current && !options.some((option) => option.value === current)) {
      options.push({
        value: current,
        label: `${__('Unknown')}: ${current}`,
      });
    }

    return options;
  }

  function unknownCountryCodes(values = []) {
    const known = new Set(countryOptions.value.map((option) => option.value));
    return (values || [])
      .map((code) => String(code || '').toUpperCase())
      .filter((code) => code && !known.has(code));
  }

  return {
    countryOptions,
    hasCountryOptions,
    buildCountrySelectOptions,
    unknownCountryCodes,
  };
}
