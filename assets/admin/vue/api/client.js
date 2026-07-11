/**
 * REST client for the Address Guard admin app.
 */

const boot = window.addressGuardAdmin || {};

const baseUrl = (boot.restUrl || '').replace(/\/$/, '');
const nonce = boot.restNonce || '';

async function request(path, { method = 'GET', body = null } = {}) {
  const url = `${baseUrl}${path}`;
  const headers = { 'X-WP-Nonce': nonce };
  const config = { method, headers, credentials: 'same-origin' };

  if (body !== null) {
    headers['Content-Type'] = 'application/json';
    config.body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(url, config);
  } catch (networkError) {
    throw new ApiError(
      __('Network error. Please check your connection and try again.'),
      0,
      networkError
    );
  }

  let data = null;
  const text = await response.text();
  if (text) {
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = null;
    }
  }

  if (!response.ok) {
    const message =
      (data && data.message) ||
      __('Something went wrong while talking to the server.');
    throw new ApiError(message, response.status, data);
  }

  return data;
}

export function __(text) {
  if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') {
    return window.wp.i18n.__(text, 'address-guard-for-woocommerce');
  }
  return text;
}

export class ApiError extends Error {
  constructor(message, status = 0, data = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

export const api = {
  getSettings: () => request('/settings'),
  saveSettings: (settings) => request('/settings', { method: 'POST', body: settings }),
};
