/**
 * WPRuby Address Checks checkout autocomplete.
 *
 * Lightweight vanilla JS with optional jQuery for WooCommerce field updates.
 */
(function (window, document) {
	'use strict';

	var config = window.wprubyAddressChecksAutocomplete || {};
	if (!config.restUrl) {
		return;
	}

	var FIELD_SUFFIXES = ['address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
	var CART_STORE = 'wc/store/cart';
	var CHECKOUT_STORE = 'wc/store/checkout';
	var debounceTimers = {};
	var activeControllers = {};
	var instances = [];

	function debugLog() {
		if (!config.debug || typeof console === 'undefined' || !console.log) {
			return;
		}

		console.log.apply(console, ['[WPRuby Address Checks autocomplete]'].concat(Array.prototype.slice.call(arguments)));
	}

	function getWpData() {
		return window.wp && window.wp.data ? window.wp.data : null;
	}

	function getBlocksCartDispatch() {
		var wpData = getWpData();

		if (!wpData) {
			return null;
		}

		try {
			return wpData.dispatch(CART_STORE);
		} catch (error) {
			return null;
		}
	}

	function getBlocksCartSelect() {
		var wpData = getWpData();

		if (!wpData) {
			return null;
		}

		try {
			return wpData.select(CART_STORE);
		} catch (error) {
			return null;
		}
	}

	function getCheckoutSelect() {
		var wpData = getWpData();

		if (!wpData) {
			return null;
		}

		try {
			return wpData.select(CHECKOUT_STORE);
		} catch (error) {
			return null;
		}
	}

	function isUseShippingAsBilling() {
		var select = getCheckoutSelect();

		return !!(select && select.getUseShippingAsBilling && select.getUseShippingAsBilling());
	}

	function isUseBillingAsShipping() {
		var forced = false;

		if (window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting) {
			forced = !!window.wc.wcSettings.getSetting('forcedBillingAddress', false);
		}

		var select = getCheckoutSelect();
		var prefersCollection = !!(select && select.prefersCollection && select.prefersCollection());

		return forced || prefersCollection;
	}

	function getBlocksCurrentAddress(type) {
		var select = getBlocksCartSelect();

		if (!select || !select.getCustomerData) {
			return {};
		}

		var customerData = select.getCustomerData() || {};

		return type === 'shipping'
			? Object.assign({}, customerData.shippingAddress || {})
			: Object.assign({}, customerData.billingAddress || {});
	}

	function buildAddressPatch(address) {
		var patch = {};

		FIELD_SUFFIXES.forEach(function (suffix) {
			if (!Object.prototype.hasOwnProperty.call(address, suffix)) {
				return;
			}

			patch[suffix] = String(address[suffix] || '');
		});

		if (patch.country) {
			patch.country = patch.country.toUpperCase();
		}

		return patch;
	}

	function setBlocksAddress(type, patch) {
		var dispatch = getBlocksCartDispatch();

		if (!dispatch) {
			return false;
		}

		if (type === 'shipping') {
			if (dispatch.setShippingAddress) {
				dispatch.setShippingAddress(patch);
			}

			if (isUseShippingAsBilling() && dispatch.setBillingAddress) {
				dispatch.setBillingAddress(patch);
			}
		} else {
			if (dispatch.setBillingAddress) {
				dispatch.setBillingAddress(patch);
			}

			if (isUseBillingAsShipping() && dispatch.setShippingAddress) {
				dispatch.setShippingAddress(patch);
			}
		}

		return true;
	}

	function dispatchCheckoutAddressEvent(type) {
		if (!window.wp || !window.wp.hooks || !window.wp.hooks.doAction) {
			return;
		}

		var select = getBlocksCartSelect();
		var cartData = select && select.getCartData ? select.getCartData() : {};
		var eventName =
			type === 'shipping'
				? 'experimental__woocommerce_blocks-checkout-set-shipping-address'
				: 'experimental__woocommerce_blocks-checkout-set-billing-address';

		try {
			window.wp.hooks.doAction(eventName, { storeCart: cartData });
		} catch (error) {
			debugLog('checkout event failed', type, error);
		}
	}

	function dispatchCheckoutAddressEvents(type) {
		dispatchCheckoutAddressEvent(type);

		if (type === 'shipping' && isUseShippingAsBilling()) {
			dispatchCheckoutAddressEvent('billing');
			return;
		}

		if (type === 'billing' && isUseBillingAsShipping()) {
			dispatchCheckoutAddressEvent('shipping');
		}
	}

	function getNativeValueSetter(element) {
		if (!element) {
			return null;
		}

		var prototype =
			element.tagName === 'SELECT'
				? window.HTMLSelectElement.prototype
				: window.HTMLInputElement.prototype;
		var descriptor = Object.getOwnPropertyDescriptor(prototype, 'value');

		return descriptor && descriptor.set ? descriptor.set : null;
	}

	function triggerBlocksFieldUpdate(field, value) {
		if (!field) {
			return;
		}

		var nativeSetter = getNativeValueSetter(field);

		if (nativeSetter) {
			nativeSetter.call(field, value);
		} else {
			field.value = value;
		}

		field.dispatchEvent(
			typeof InputEvent === 'function'
				? new InputEvent('input', {
						bubbles: true,
						inputType: 'insertReplacementText',
						data: value,
					})
				: new Event('input', { bubbles: true })
		);
		field.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function applyAddressToClassicCheckout(type, address, mode) {
		mode = mode || 'classic';
		var instance = getInstance(type, mode);
		if (instance) {
			instance.suppressSearches(3000);
		}

		var countryChanged = false;
		var currentCountry = getCountryValue(type, mode);
		var nextCountry = String(address.country || '').toUpperCase();

		if (nextCountry && nextCountry !== currentCountry) {
			setFieldValue(type, 'country', nextCountry, mode);
			countryChanged = true;
		}

		var applyFields = function () {
			setFieldValue(type, 'address_1', address.address_1, mode);
			if (address.address_2) {
				setFieldValue(type, 'address_2', address.address_2, mode);
			}
			setFieldValue(type, 'city', address.city, mode);
			setFieldValue(type, 'state', address.state, mode);
			setFieldValue(type, 'postcode', address.postcode, mode);

			if (window.jQuery && mode === 'classic') {
				window.jQuery(document.body).trigger('update_checkout');
			}
		};

		if (countryChanged && mode === 'classic' && window.jQuery) {
			window.jQuery(document.body).one('updated_checkout', function () {
				window.setTimeout(applyFields, 50);
			});
			window.setTimeout(applyFields, 600);
		} else {
			window.setTimeout(applyFields, countryChanged ? 250 : 0);
		}
	}

	function applyBlocksAddressPatch(type, currentAddress, patch) {
		var merged = Object.assign({}, currentAddress, patch);

		debugLog('apply blocks address', {
			type: type,
			before: currentAddress,
			patch: patch,
			after: merged,
		});

		if (!setBlocksAddress(type, merged)) {
			return false;
		}

		dispatchCheckoutAddressEvents(type);
		return true;
	}

	function applyAddressToCheckoutBlocks(type, address) {
		var mode = 'blocks';
		var instance = getInstance(type, mode);
		if (instance) {
			instance.suppressSearches(3000);
		}

		var run = function () {
			var currentAddress = getBlocksCurrentAddress(type);
			var patch = buildAddressPatch(address);
			var nextCountry = String(patch.country || currentAddress.country || '').toUpperCase();
			var currentCountry = String(currentAddress.country || '').toUpperCase();
			var countryChanged = nextCountry && nextCountry !== currentCountry;

			debugLog('blocks selection', {
				type: type,
				currentAddress: currentAddress,
				patch: patch,
				countryChanged: countryChanged,
			});

			if (countryChanged) {
				var countryPatch = Object.assign({}, patch, {
					country: nextCountry,
					state: patch.state || '',
				});

				if (
					!applyBlocksAddressPatch(type, currentAddress, {
						country: nextCountry,
						state: '',
					})
				) {
					debugLog('blocks store unavailable, using DOM fallback');
					applyAddressToClassicCheckout(type, address, mode);
					return;
				}

				window.setTimeout(function () {
					applyBlocksAddressPatch(type, getBlocksCurrentAddress(type), countryPatch);
					debugLog('blocks store after country apply', getBlocksCurrentAddress(type));
				}, 300);
				return;
			}

			if (!applyBlocksAddressPatch(type, currentAddress, patch)) {
				debugLog('blocks store unavailable, using DOM fallback');
				applyAddressToClassicCheckout(type, address, mode);
				return;
			}

			debugLog('blocks store after apply', getBlocksCurrentAddress(type));
		};

		if (getBlocksCartDispatch()) {
			run();
			return;
		}

		var attempts = 0;
		var waitForStore = function () {
			attempts += 1;

			if (getBlocksCartDispatch()) {
				run();
				return;
			}

			if (attempts < 20) {
				window.setTimeout(waitForStore, 100);
				return;
			}

			debugLog('blocks store never became available, using DOM fallback');
			applyAddressToClassicCheckout(type, address, mode);
		};

		waitForStore();
	}

	function populateAddress(type, address, mode) {
		if (mode === 'blocks') {
			applyAddressToCheckoutBlocks(type, address);
			return;
		}

		applyAddressToClassicCheckout(type, address, mode);
	}

	function $(selector, root) {
		return (root || document).querySelector(selector);
	}

	function $all(selector, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(selector));
	}

	function debounce(key, fn, delay) {
		if (debounceTimers[key]) {
			window.clearTimeout(debounceTimers[key]);
		}
		debounceTimers[key] = window.setTimeout(fn, delay);
	}

	function abortRequest(key) {
		if (activeControllers[key]) {
			activeControllers[key].abort();
			delete activeControllers[key];
		}
	}

	function clearDebounce(key) {
		if (debounceTimers[key]) {
			window.clearTimeout(debounceTimers[key]);
			delete debounceTimers[key];
		}
	}

	function fieldSelectors(type, mode) {
		var prefix = type;
		var map = {};

		FIELD_SUFFIXES.forEach(function (suffix) {
			if (mode === 'blocks') {
				map[suffix] = '#' + prefix + '-' + suffix;
			} else {
				map[suffix] = '#' + prefix + '_' + suffix;
			}
		});

		return map;
	}

	function findField(type, suffix, mode) {
		var selectors = fieldSelectors(type, mode);
		var selector = selectors[suffix];
		var el = selector ? $(selector) : null;

		if (el) {
			return el;
		}

		if (mode === 'blocks') {
			var blocksSelector = '.wc-block-components-address-form input[id="' + type + '-' + suffix + '"]';
			el = $(blocksSelector);
			if (el) {
				return el;
			}
		}

		return null;
	}

	function getFieldValue(type, suffix, mode) {
		var field = findField(type, suffix, mode);
		return field ? String(field.value || '').trim() : '';
	}

	function getCountryValue(type, mode) {
		return getFieldValue(type, 'country', mode).toUpperCase();
	}

	function getInstance(type, mode) {
		for (var i = 0; i < instances.length; i++) {
			if (instances[i].type === type && instances[i].mode === mode) {
				return instances[i];
			}
		}

		return null;
	}

	function triggerFieldUpdate(el) {
		if (!el) {
			return;
		}

		el.dispatchEvent(new Event('change', { bubbles: true }));

		if (window.jQuery) {
			var $el = window.jQuery(el);
			$el.trigger('change');

			if ($el.data('select2')) {
				$el.trigger('change.select2');
			}
		}
	}

	function setFieldValue(type, suffix, value, mode) {
		if (!value && suffix !== 'address_2') {
			return;
		}

		var field = findField(type, suffix, mode);
		if (!field) {
			return;
		}

		if (mode === 'blocks') {
			if (field.tagName === 'SELECT') {
				var blocksOption = $all('option', field).find(function (opt) {
					return String(opt.value).toUpperCase() === String(value).toUpperCase();
				});

				triggerBlocksFieldUpdate(field, blocksOption ? blocksOption.value : value);
				return;
			}

			triggerBlocksFieldUpdate(field, value);
			return;
		}

		if (field.tagName === 'SELECT') {
			var option = $all('option', field).find(function (opt) {
				return String(opt.value).toUpperCase() === String(value).toUpperCase();
			});

			if (option) {
				field.value = option.value;
			} else {
				field.value = value;
			}
		} else {
			field.value = value;
		}

		triggerFieldUpdate(field);
	}

	function buildSearchUrl(query, addressType, country, parentId) {
		var url = new URL(config.restUrl + 'address/autocomplete', window.location.origin);
		url.searchParams.set('query', query || '');
		url.searchParams.set('address_type', addressType);

		if (country) {
			url.searchParams.set('country', country);
		}

		if (parentId) {
			url.searchParams.set('parent_id', parentId);
		}

		return url.toString();
	}

	function buildDetailsUrl(placeId, type) {
		var url = new URL(config.restUrl + 'address/details', window.location.origin);
		url.searchParams.set('place_id', placeId);
		url.searchParams.set('type', type);

		if (config.autocompleteProvider) {
			url.searchParams.set('provider', config.autocompleteProvider);
		}

		return url.toString();
	}

	function fetchAddressDetails(placeId, type, signal) {
		return window
			.fetch(buildDetailsUrl(placeId, type), {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': config.restNonce || '',
				},
				signal: signal,
			})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed');
				}
				return response.json();
			});
	}

	function createSvgIcon(type) {
		var ns = 'http://www.w3.org/2000/svg';
		var svg = document.createElementNS(ns, 'svg');
		svg.setAttribute('viewBox', '0 0 24 24');
		svg.setAttribute('fill', 'none');
		svg.setAttribute('stroke', 'currentColor');
		svg.setAttribute('stroke-width', '1.75');
		svg.setAttribute('stroke-linecap', 'round');
		svg.setAttribute('stroke-linejoin', 'round');
		svg.setAttribute('aria-hidden', 'true');

		if (type === 'search') {
			var circle = document.createElementNS(ns, 'circle');
			circle.setAttribute('cx', '11');
			circle.setAttribute('cy', '11');
			circle.setAttribute('r', '7');
			svg.appendChild(circle);

			var line = document.createElementNS(ns, 'line');
			line.setAttribute('x1', '16.5');
			line.setAttribute('y1', '16.5');
			line.setAttribute('x2', '21');
			line.setAttribute('y2', '21');
			svg.appendChild(line);
		} else {
			var path = document.createElementNS(ns, 'path');
			path.setAttribute(
				'd',
				'M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z'
			);
			svg.appendChild(path);

			var dot = document.createElementNS(ns, 'circle');
			dot.setAttribute('cx', '12');
			dot.setAttribute('cy', '11');
			dot.setAttribute('r', '2.25');
			svg.appendChild(dot);
		}

		return svg;
	}

	function formatOptionMain(item) {
		if (item.address && item.address.address_1) {
			return item.address.address_1;
		}

		var label = String(item.label || '');
		var lines = label.split(/\n+/).map(function (line) {
			return line.trim();
		}).filter(Boolean);

		return lines[0] || label;
	}

	function formatOptionMeta(item) {
		var address = item.address;
		if (!address) {
			var label = String(item.label || '');
			var lines = label.split(/\n+/).map(function (line) {
				return line.trim();
			}).filter(Boolean);

			if (lines.length > 1) {
				return lines.slice(1).join(', ');
			}

			return '';
		}

		var parts = [];
		var cityLine = [];

		if (address.postcode) {
			parts.push(address.postcode);
		}

		if (address.city) {
			cityLine.push(address.city);
		}

		if (address.country) {
			cityLine.push(address.country);
		}

		if (cityLine.length) {
			parts.push(cityLine.join(', '));
		}

		return parts.join(' ');
	}

	function Autocomplete(input, type, mode) {
		this.input = input;
		this.type = type;
		this.mode = mode || (config.checkoutBlocks ? 'blocks' : 'classic');
		this.dropdown = null;
		this.list = null;
		this.items = [];
		this.activeIndex = -1;
		this.isOpen = false;
		this.skipSearch = false;
		this.suppressUntil = 0;
		this.searchGeneration = 0;
		this.selectionGeneration = 0;
		this.detailsController = null;
		this.requestKey = type + ':' + (input.id || input.name || Math.random());
		this.repositionHandler = null;

		this.wrap = document.createElement('div');
		this.wrap.className = 'wpruby-ag-autocomplete';
		input.parentNode.insertBefore(this.wrap, input);
		this.wrap.appendChild(input);

		this.announcer = document.createElement('div');
		this.announcer.className = 'wpruby-ag-autocomplete__announcer';
		this.announcer.setAttribute('aria-live', 'polite');
		this.announcer.setAttribute('aria-atomic', 'true');
		this.wrap.appendChild(this.announcer);

		this.dropdown = document.createElement('div');
		this.dropdown.className = 'wpruby-ag-autocomplete-dropdown';
		this.dropdown.hidden = true;
		this.wrap.appendChild(this.dropdown);

		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-expanded', 'false');

		this.bindEvents();
	}

	Autocomplete.prototype.suppressSearches = function (durationMs) {
		this.suppressUntil = Date.now() + (durationMs || 2000);
		this.invalidateSearch();
		this.items = [];
		this.activeIndex = -1;
		this.close();
		this.announce('');
	};

	Autocomplete.prototype.isSearchSuppressed = function () {
		return this.suppressUntil > Date.now();
	};

	Autocomplete.prototype.bindEvents = function () {
		var self = this;

		this.input.addEventListener('input', function () {
			self.onInput();
		});

		this.input.addEventListener('keydown', function (event) {
			self.onKeydown(event);
		});

		this.input.addEventListener('blur', function () {
			window.setTimeout(function () {
				self.close();
			}, 150);
		});

		document.addEventListener('click', function (event) {
			if (!self.wrap.contains(event.target) && !self.dropdown.contains(event.target)) {
				self.close();
			}
		});
	};

	Autocomplete.prototype.clearPendingSearch = function () {
		abortRequest(this.requestKey);
		clearDebounce(this.requestKey);
	};

	Autocomplete.prototype.abortDetailsFetch = function () {
		if (this.detailsController) {
			this.detailsController.abort();
			this.detailsController = null;
		}
	};

	Autocomplete.prototype.invalidateSearch = function () {
		this.searchGeneration += 1;
		this.clearPendingSearch();
	};

	Autocomplete.prototype.onInput = function () {
		var self = this;

		if (this.isSearchSuppressed()) {
			this.close();
			this.announce('');
			return;
		}

		if (this.skipSearch) {
			this.skipSearch = false;
			this.invalidateSearch();
			this.close();
			this.announce('');
			return;
		}

		var query = String(this.input.value || '').trim();
		var minChars = parseInt(config.minChars, 10) || 3;

		if (query.length < minChars) {
			this.invalidateSearch();
			if (query.length > 0) {
				this.showQueryTooShort();
			} else {
				this.close();
				this.announce('');
			}
			return;
		}

		debounce(
			this.requestKey,
			function () {
				self.search(query);
			},
			parseInt(config.debounceMs, 10) || 300
		);
	};

	Autocomplete.prototype.search = function (query, parentId) {
		var self = this;

		if (this.isSearchSuppressed()) {
			return;
		}

		var country = getCountryValue(this.type, this.mode);
		var url = buildSearchUrl(query, this.type, country, parentId || '');

		this.clearPendingSearch();
		var generation = ++this.searchGeneration;
		this.showLoading();

		var controller = new AbortController();
		activeControllers[this.requestKey] = controller;

		window
			.fetch(url, {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': config.restNonce || '',
				},
				signal: controller.signal,
			})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed');
				}
				return response.json();
			})
			.then(function (data) {
				if (generation !== self.searchGeneration || self.isSearchSuppressed()) {
					return;
				}

				if (!Array.isArray(data)) {
					data = [];
				}

				self.items = data;
				self.activeIndex = -1;

				if (!data.length) {
					self.showEmpty();
				} else {
					self.renderItems(data);
					self.open();
				}
			})
			.catch(function (error) {
				if (error && error.name === 'AbortError') {
					return;
				}

				if (generation !== self.searchGeneration || self.isSearchSuppressed()) {
					return;
				}

				self.showError();
			})
			.finally(function () {
				if (activeControllers[self.requestKey] === controller) {
					delete activeControllers[self.requestKey];
				}
			});
	};

	Autocomplete.prototype.showLoading = function () {
		var i18n = config.i18n || {};
		this.items = [];
		this.activeIndex = -1;
		this.dropdown.innerHTML = '';

		var loading = document.createElement('div');
		loading.className = 'wpruby-ag-autocomplete-loading';
		loading.setAttribute('role', 'status');

		var spinner = document.createElement('span');
		spinner.className = 'wpruby-ag-autocomplete-loading__spinner';
		spinner.setAttribute('aria-hidden', 'true');
		loading.appendChild(spinner);

		var text = document.createElement('span');
		text.className = 'wpruby-ag-autocomplete-loading__text';
		text.textContent = i18n.loading || 'Searching addresses…';
		loading.appendChild(text);

		this.dropdown.appendChild(loading);
		this.list = null;
		this.open();
	};

	Autocomplete.prototype.showQueryTooShort = function () {
		var i18n = config.i18n || {};

		this.items = [];
		this.activeIndex = -1;
		this.dropdown.innerHTML = '';
		this.list = null;

		var hint = document.createElement('div');
		hint.className = 'wpruby-ag-autocomplete-empty';
		hint.setAttribute('role', 'status');

		var content = document.createElement('div');
		content.className = 'wpruby-ag-autocomplete-empty__content';

		var messageEl = document.createElement('p');
		messageEl.className = 'wpruby-ag-autocomplete-empty__message';
		messageEl.textContent = i18n.queryTooShort || 'Keep typing to search for an address.';
		content.appendChild(messageEl);

		hint.appendChild(content);
		this.dropdown.appendChild(hint);
		this.open();
	};

	Autocomplete.prototype.showEmpty = function () {
		var i18n = config.i18n || {};
		var title = i18n.noResultsFound || 'No address matches found';
		var message = i18n.noResultsFoundHint || 'Check the street name or selected country and try again.';

		this.dropdown.innerHTML = '';
		this.list = null;

		var empty = document.createElement('div');
		empty.className = 'wpruby-ag-autocomplete-empty';
		empty.setAttribute('role', 'status');

		var icon = document.createElement('span');
		icon.className = 'wpruby-ag-autocomplete-empty__icon';
		icon.appendChild(createSvgIcon('search'));
		empty.appendChild(icon);

		var content = document.createElement('div');
		content.className = 'wpruby-ag-autocomplete-empty__content';

		var titleEl = document.createElement('p');
		titleEl.className = 'wpruby-ag-autocomplete-empty__title';
		titleEl.textContent = title;
		content.appendChild(titleEl);

		var messageEl = document.createElement('p');
		messageEl.className = 'wpruby-ag-autocomplete-empty__message';
		messageEl.textContent = message;
		content.appendChild(messageEl);

		empty.appendChild(content);
		this.dropdown.appendChild(empty);
		this.open();
	};

	Autocomplete.prototype.showError = function () {
		var i18n = config.i18n || {};

		this.dropdown.innerHTML = '';
		this.list = null;
		this.items = [];
		this.activeIndex = -1;

		var error = document.createElement('div');
		error.className = 'wpruby-ag-autocomplete-error';
		error.setAttribute('role', 'status');

		var icon = document.createElement('span');
		icon.className = 'wpruby-ag-autocomplete-error__icon';
		icon.appendChild(createSvgIcon('location'));
		error.appendChild(icon);

		var content = document.createElement('div');
		content.className = 'wpruby-ag-autocomplete-error__content';

		var titleEl = document.createElement('p');
		titleEl.className = 'wpruby-ag-autocomplete-error__title';
		titleEl.textContent = i18n.errorTitle || 'Address search is temporarily unavailable';
		content.appendChild(titleEl);

		var messageEl = document.createElement('p');
		messageEl.className = 'wpruby-ag-autocomplete-error__message';
		messageEl.textContent = i18n.errorHint || 'You can still enter the address manually.';
		content.appendChild(messageEl);

		error.appendChild(content);
		this.dropdown.appendChild(error);
		this.open();
	};

	Autocomplete.prototype.renderItems = function (items) {
		var self = this;
		this.dropdown.innerHTML = '';

		this.list = document.createElement('ul');
		this.list.className = 'wpruby-ag-autocomplete-list';
		this.list.setAttribute('role', 'listbox');
		this.list.id = 'wpruby-ag-list-' + this.requestKey.replace(/[^a-z0-9_-]/gi, '');
		this.input.setAttribute('aria-controls', this.list.id);

		items.forEach(function (item, index) {
			var li = document.createElement('li');
			li.className = 'wpruby-ag-autocomplete-option';
			li.setAttribute('role', 'option');
			li.dataset.index = String(index);

			var main = document.createElement('span');
			main.className = 'wpruby-ag-autocomplete-option__main';
			main.textContent = formatOptionMain(item);
			li.appendChild(main);

			var metaText = formatOptionMeta(item);
			if (metaText) {
				var meta = document.createElement('span');
				meta.className = 'wpruby-ag-autocomplete-option__meta';
				meta.textContent = metaText;
				li.appendChild(meta);
			}

			li.addEventListener('mousedown', function (event) {
				event.preventDefault();
			});

			li.addEventListener('click', function () {
				self.select(index);
			});

			self.list.appendChild(li);
		});

		this.dropdown.appendChild(this.list);
	};

	Autocomplete.prototype.highlight = function () {
		var activeIndex = this.activeIndex;
		var options = $all('.wpruby-ag-autocomplete-option', this.dropdown);
		options.forEach(function (option, index) {
			option.classList.toggle('wpruby-ag-autocomplete-option--active', index === activeIndex);
			option.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false');
		});
	};

	Autocomplete.prototype.onKeydown = function (event) {
		if (!this.isOpen && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
			if (this.items.length) {
				this.open();
			}
		}

		if (!this.items.length) {
			if (event.key === 'Escape') {
				this.close();
			}
			return;
		}

		if (event.key === 'ArrowDown') {
			event.preventDefault();
			this.activeIndex = Math.min(this.activeIndex + 1, this.items.length - 1);
			this.highlight();
		} else if (event.key === 'ArrowUp') {
			event.preventDefault();
			this.activeIndex = Math.max(this.activeIndex - 1, 0);
			this.highlight();
		} else if (event.key === 'Enter') {
			if (this.isOpen && this.activeIndex >= 0) {
				event.preventDefault();
				this.select(this.activeIndex);
			}
		} else if (event.key === 'Escape') {
			this.close();
		}
	};

	Autocomplete.prototype.select = function (index) {
		var self = this;
		var item = this.items[index];
		if (!item) {
			return;
		}

		if (item.is_container) {
			this.suppressSearches(300);
			this.skipSearch = true;
			this.clearPendingSearch();
			this.abortDetailsFetch();
			this.input.value = item.label || this.input.value;
			triggerFieldUpdate(this.input);
			this.search(this.input.value || '', item.id);
			return;
		}

		this.suppressSearches(3000);
		this.skipSearch = true;
		this.selectionGeneration += 1;
		this.clearPendingSearch();
		this.abortDetailsFetch();

		var selectionGeneration = this.selectionGeneration;

		var applySelection = function (address, label) {
			if (selectionGeneration !== self.selectionGeneration) {
				return;
			}

			debugLog('selected suggestion', {
				type: self.type,
				mode: self.mode,
				address: address,
			});

			if (self.mode === 'blocks') {
				populateAddress(self.type, address, self.mode);
				self.close();
				return;
			}

			self.input.value = address.address_1 || label;
			triggerFieldUpdate(self.input);
			populateAddress(self.type, address, self.mode);
			self.close();
		};

		if (item.address && !item.requires_details) {
			applySelection(item.address, item.label);
			return;
		}

		this.announce(config.i18n.detailsLoading || 'Loading address details…');
		this.close();

		var controller = new AbortController();
		this.detailsController = controller;

		fetchAddressDetails(item.id, this.type, controller.signal)
			.then(function (details) {
				if (selectionGeneration !== self.selectionGeneration) {
					return;
				}

				var address = null;
				if (details && details.address && typeof details.address === 'object') {
					address = details.address;
				} else if (details && details.address_1) {
					address = {
						address_1: details.address_1 || '',
						address_2: details.address_2 || '',
						city: details.city || '',
						state: details.state || '',
						postcode: details.postcode || '',
						country: details.country || '',
					};
				}
				if (!address) {
					throw new Error('Missing address details');
				}

				applySelection(address, item.label);
				self.announce('');
			})
			.catch(function (error) {
				if (error && error.name === 'AbortError') {
					return;
				}

				if (selectionGeneration !== self.selectionGeneration) {
					return;
				}

				if (self.mode === 'blocks') {
					self.announce(config.i18n.detailsError || 'Could not load address details.');
					return;
				}

				self.input.value = item.label;
				triggerFieldUpdate(self.input);
				self.announce(config.i18n.detailsError || 'Could not load address details.');
			})
			.finally(function () {
				if (self.detailsController === controller) {
					self.detailsController = null;
				}
			});
	};

	Autocomplete.prototype.announce = function (message) {
		this.announcer.textContent = message || '';
	};

	Autocomplete.prototype.addRepositionListeners = function () {
		var self = this;

		if (this.repositionHandler) {
			return;
		}

		this.repositionHandler = function () {
			self.positionDropdown();
		};

		window.addEventListener('resize', this.repositionHandler);
		window.addEventListener('scroll', this.repositionHandler, true);
	};

	Autocomplete.prototype.removeRepositionListeners = function () {
		if (!this.repositionHandler) {
			return;
		}

		window.removeEventListener('resize', this.repositionHandler);
		window.removeEventListener('scroll', this.repositionHandler, true);
		this.repositionHandler = null;
	};

	Autocomplete.prototype.positionDropdown = function () {
		if (!this.dropdown || this.dropdown.hidden) {
			return;
		}

		if (this.dropdown.parentNode !== document.body) {
			document.body.appendChild(this.dropdown);
		}

		var rect = this.input.getBoundingClientRect();
		this.dropdown.style.position = 'fixed';
		this.dropdown.style.top = Math.round(rect.bottom + 6) + 'px';
		this.dropdown.style.left = Math.round(rect.left) + 'px';
		this.dropdown.style.width = Math.round(rect.width) + 'px';
		this.dropdown.style.right = 'auto';
		this.dropdown.style.zIndex = '9999';
	};

	Autocomplete.prototype.open = function () {
		if (this.isSearchSuppressed()) {
			this.close();
			return;
		}

		this.isOpen = true;
		this.dropdown.hidden = false;
		this.input.setAttribute('aria-expanded', 'true');
		this.positionDropdown();
		this.addRepositionListeners();
	};

	Autocomplete.prototype.close = function () {
		this.isOpen = false;
		this.activeIndex = -1;
		this.dropdown.hidden = true;
		this.dropdown.innerHTML = '';
		this.list = null;
		this.input.setAttribute('aria-expanded', 'false');
		this.input.removeAttribute('aria-controls');
		this.removeRepositionListeners();

		if (this.dropdown.parentNode === document.body) {
			this.wrap.appendChild(this.dropdown);
			this.dropdown.style.position = '';
			this.dropdown.style.top = '';
			this.dropdown.style.left = '';
			this.dropdown.style.width = '';
			this.dropdown.style.right = '';
			this.dropdown.style.zIndex = '';
		}
	};

	function attachAutocomplete(input, type, mode) {
		if (!input || input.dataset.wprubyAcBound === '1') {
			return;
		}

		input.dataset.wprubyAcBound = '1';
		instances.push(new Autocomplete(input, type, mode));
	}

	function initClassic() {
		if (config.billingEnabled) {
			$all('[data-wpruby-ac-autocomplete="billing"], #billing_address_1').forEach(function (input) {
				attachAutocomplete(input, 'billing', 'classic');
			});
		}

		if (config.shippingEnabled) {
			$all('[data-wpruby-ac-autocomplete="shipping"], #shipping_address_1').forEach(function (input) {
				attachAutocomplete(input, 'shipping', 'classic');
			});
		}
	}

	window.wprubyAcClassicInit = initClassic;

	function resolveMode() {
		if (config.checkoutMode === 'blocks') {
			return 'blocks';
		}

		if (config.checkoutMode === 'classic') {
			return 'classic';
		}

		var blocksRoot = $('.wp-block-woocommerce-checkout') || $('.wc-block-checkout');
		var classicField = $('#billing_address_1') || $('[data-wpruby-ac-autocomplete="billing"]');
		var classicForm = $('form.checkout.woocommerce-checkout') || $('form.woocommerce-checkout');

		if (classicField || classicForm) {
			return 'classic';
		}

		if (blocksRoot) {
			return 'blocks';
		}

		return config.checkoutBlocks ? 'blocks' : 'classic';
	}

	function initBlocks() {
		var billingSelector = '#billing-address_1, input[id="billing-address_1"]';
		var shippingSelector = '#shipping-address_1, input[id="shipping-address_1"]';

		if (config.billingEnabled) {
			$all(billingSelector).forEach(function (input) {
				attachAutocomplete(input, 'billing', 'blocks');
			});
		}

		if (config.shippingEnabled) {
			$all(shippingSelector).forEach(function (input) {
				attachAutocomplete(input, 'shipping', 'blocks');
			});
		}
	}

	function observeBlocks() {
		var observer = new MutationObserver(function () {
			initBlocks();
		});

		var root = $('.wp-block-woocommerce-checkout') || $('.wc-block-checkout') || document.body;
		observer.observe(root, { childList: true, subtree: true });
		initBlocks();
	}

	function init() {
		var mode = resolveMode();

		if (mode === 'blocks') {
			observeBlocks();
			return;
		}

		initClassic();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
