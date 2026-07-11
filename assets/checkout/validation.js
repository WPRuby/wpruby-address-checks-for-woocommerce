/**
 * Address Guard checkout validation UI helpers.
 *
 * Classic checkout: inline notices are inserted near each address section.
 * Checkout Blocks: DOM-based insertion near address forms (MutationObserver).
 */
(function (window, document) {
	'use strict';

	var config = window.addressGuardCheckout || {};
	if (!config.restUrl) {
		return;
	}

	var FIELD_SUFFIXES = ['address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
	var activeRequests = {};

	function $(selector, root) {
		return (root || document).querySelector(selector);
	}

	function findField(type, suffix, mode) {
		var selector = mode === 'blocks' ? '#' + type + '-' + suffix : '#' + type + '_' + suffix;
		var el = $(selector);

		if (el) {
			return el;
		}

		if (mode === 'blocks') {
			return $('.wc-block-components-address-form input[id="' + type + '-' + suffix + '"]')
				|| $('.wc-block-components-address-form select[id="' + type + '-' + suffix + '"]');
		}

		return null;
	}

	function findAddressSection(type, mode) {
		if (mode === 'blocks') {
			var field = findField(type, 'address_1', mode);
			if (field) {
				return field.closest('.wc-block-components-address-form')
					|| field.closest('.wc-block-components-address-card');
			}

			return $('.wc-block-components-address-form');
		}

		var classicField = findField(type, 'address_1', mode);
		if (classicField) {
			return classicField.closest('.woocommerce-' + type + '-fields')
				|| classicField.closest('.col-' + (type === 'billing' ? '1' : '2'));
		}

		return $('.' + type + '_address_fields') || $('.woocommerce-' + type + '-fields');
	}

	function readAddress(type, mode) {
		var address = { type: type };

		FIELD_SUFFIXES.forEach(function (suffix) {
			var field = findField(type, suffix, mode);
			address[suffix] = field ? String(field.value || '').trim() : '';
		});

		return address;
	}

	function ensureSectionNoticeHost(type, mode) {
		var hostId = 'address-guard-validation-notices-' + type;
		var host = document.getElementById(hostId);

		if (host) {
			return host;
		}

		host = document.createElement('div');
		host.id = hostId;
		host.className = 'address-guard-validation-notices address-guard-validation-notices--' + type;
		host.setAttribute('data-address-type', type);

		var section = findAddressSection(type, mode);
		if (section) {
			section.insertBefore(host, section.firstChild);
			return host;
		}

		var fallback = document.getElementById('address-guard-validation-notices');
		if (!fallback) {
			fallback = document.createElement('div');
			fallback.id = 'address-guard-validation-notices';
			fallback.className = 'address-guard-validation-notices';

			var checkoutForm = $('form.checkout') || $('form.woocommerce-checkout') || $('.wc-block-checkout__form');
			if (checkoutForm) {
				checkoutForm.insertBefore(fallback, checkoutForm.firstChild);
			} else {
				document.body.insertBefore(fallback, document.body.firstChild);
			}
		}

		fallback.appendChild(host);
		return host;
	}

	function clearSectionNotice(type) {
		var host = document.getElementById('address-guard-validation-notices-' + type);
		if (host) {
			host.innerHTML = '';
		}
	}

	function renderNotice(result, type, mode) {
		var host = ensureSectionNoticeHost(type, mode);
		var checkoutMeta = result.checkout || {};
		var message = result.message || checkoutMeta.notice || '';
		var className = 'address-guard-validation-notice';

		host.innerHTML = '';

		if (!message) {
			return;
		}

		className += checkoutMeta.block ? ' is-error' : ' is-warning';

		var notice = document.createElement('div');
		notice.className = className;
		notice.setAttribute('role', 'alert');

		var title = document.createElement('span');
		title.className = 'address-guard-validation-notice__title';
		title.textContent = message;
		notice.appendChild(title);
		host.appendChild(notice);

		if (checkoutMeta.block) {
			var addressField = findField(type, 'address_1', mode);
			if (addressField) {
				addressField.setAttribute('aria-invalid', 'true');
				addressField.classList.add('address-guard-field-error');
			}
		}
	}

	function validateAddress(type, mode) {
		if ((type === 'billing' && !config.billingEnabled) || (type === 'shipping' && !config.shippingEnabled)) {
			return Promise.resolve(null);
		}

		var address = readAddress(type, mode);
		if (!address.address_1 && !address.city && !address.postcode) {
			clearSectionNotice(type);
			return Promise.resolve(null);
		}

		if (activeRequests[type]) {
			activeRequests[type].abort();
		}

		var controller = new AbortController();
		activeRequests[type] = controller;

		return fetch(config.restUrl + 'address/validate', {
			method: 'POST',
			credentials: 'same-origin',
			signal: controller.signal,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.restNonce
			},
			body: JSON.stringify({
				address: address,
				type: type,
				context: 'checkout'
			})
		})
			.then(function (response) {
				if (!response.ok) {
					return null;
				}
				return response.json();
			})
			.catch(function () {
				return null;
			})
			.finally(function () {
				delete activeRequests[type];
			});
	}

	function bindType(type, mode) {
		var field = findField(type, 'address_1', mode);
		if (!field || field.getAttribute('data-address-guard-validation-bound') === '1') {
			return;
		}

		field.setAttribute('data-address-guard-validation-bound', '1');

		field.addEventListener('blur', function () {
			validateAddress(type, mode).then(function (result) {
				if (!result || result.status === 'valid' || result.status === 'skipped') {
					clearSectionNotice(type);
					field.removeAttribute('aria-invalid');
					field.classList.remove('address-guard-field-error');
					return;
				}
				renderNotice(result, type, mode);
			});
		});

		FIELD_SUFFIXES.forEach(function (suffix) {
			var relatedField = findField(type, suffix, mode);
			if (!relatedField || relatedField === field) {
				return;
			}

			relatedField.addEventListener('input', function () {
				clearSectionNotice(type);
				var addressField = findField(type, 'address_1', mode);
				if (addressField) {
					addressField.removeAttribute('aria-invalid');
					addressField.classList.remove('address-guard-field-error');
				}
			});
		});
	}

	function resolveMode() {
		if (config.checkoutMode === 'blocks') {
			return 'blocks';
		}

		if (config.checkoutMode === 'classic') {
			return 'classic';
		}

		var blocksRoot = $('.wp-block-woocommerce-checkout') || $('.wc-block-checkout');
		var classicField = $('#billing_address_1');
		var classicForm = $('form.checkout.woocommerce-checkout') || $('form.woocommerce-checkout');

		if (classicField || classicForm) {
			return 'classic';
		}

		if (blocksRoot) {
			return 'blocks';
		}

		return config.checkoutBlocks ? 'blocks' : 'classic';
	}

	function init(mode) {
		if (config.billingEnabled) {
			bindType('billing', mode);
		}
		if (config.shippingEnabled) {
			bindType('shipping', mode);
		}
	}

	function boot() {
		var mode = resolveMode();
		init(mode);

		if (mode === 'blocks' && window.MutationObserver) {
			var observer = new MutationObserver(function () {
				init('blocks');
			});
			observer.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(window, document);
