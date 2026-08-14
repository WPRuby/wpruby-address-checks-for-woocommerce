/**
 * Build Address Guard Pro product URLs with plugin-admin UTM params.
 *
 * Fallbacks only — live URLs come from REST meta via ProUrls in PHP.
 *
 * @param {string} utmContent CTA location slug.
 * @returns {string}
 */
export function getProUrl(utmContent) {
  const content = String(utmContent || 'upgrade_tab')
    .toLowerCase()
    .replace(/[^a-z0-9_\-]/g, '');

  const params = new URLSearchParams({
    utm_source: 'address_guard_free',
    utm_medium: 'plugin_admin',
    utm_campaign: 'free_to_pro',
    utm_content: content || 'upgrade_tab',
  });

  return `https://wpruby.com/plugin/woocommerce-address-guard-pro/?${params.toString()}`;
}
