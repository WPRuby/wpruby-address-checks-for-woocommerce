.PHONY: install test test-unit test-integration assets build clean

PHP ?= php
COMPOSER ?= composer
NPM ?= npm

PLUGIN_SLUG := checkout-address-guard-for-woocommerce
VERSION := $(shell $(PHP) -r '$$c=file_get_contents("address-guard-for-woocommerce.php"); if (preg_match("/Version:\\s+([0-9.]+)/", $$c, $$m)) { echo $$m[1]; }')
BUILD_DIR := build/$(PLUGIN_SLUG)
DIST_DIR := dist
ZIP_FILE := $(DIST_DIR)/$(PLUGIN_SLUG).zip
ZIP_VERSIONED := $(DIST_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip

# Production ZIP: keep human-readable Vue source + build tooling required by
# WordPress.org when shipping compiled/minified admin assets. Exclude tests,
# vendor, node_modules, IDE folders, and local build leftovers.
RSYNC_EXCLUDES := \
	--exclude .git \
	--exclude .github \
	--exclude .gitignore \
	--exclude .DS_Store \
	--exclude .idea \
	--exclude .vscode \
	--exclude node_modules \
	--exclude vendor \
	--exclude tests \
	--exclude /build \
	--exclude /dist \
	--exclude phpunit.xml.dist \
	--exclude package-lock.json \
	--exclude Makefile \
	--exclude composer.json \
	--exclude composer.lock \
	--exclude .phpunit.result.cache \
	--exclude docs \
	--exclude scripts \
	--exclude assets/frontend \
	--exclude '*.map' \
	--exclude '*/.DS_Store'

install:
	$(COMPOSER) install --no-interaction --prefer-dist
	$(NPM) ci

test: test-unit test-integration

test-unit:
	$(PHP) vendor/bin/phpunit --testsuite Unit

test-integration:
	$(PHP) vendor/bin/phpunit --testsuite Integration

assets:
	$(NPM) run build

build: assets
	rm -rf "$(BUILD_DIR)" "$(ZIP_FILE)" "$(ZIP_VERSIONED)"
	mkdir -p "$(BUILD_DIR)" "$(DIST_DIR)"
	rsync -a $(RSYNC_EXCLUDES) ./ "$(BUILD_DIR)/"
	# Remove any leftover Pro-named artifacts that must never ship with Lite.
	rm -rf build/woocommerce-address-guard-pro "$(DIST_DIR)/woocommerce-address-guard-pro"*
	find "$(BUILD_DIR)" -name '.DS_Store' -delete
	cd build && zip -rq "../$(ZIP_FILE)" "$(PLUGIN_SLUG)"
	cp "$(ZIP_FILE)" "$(ZIP_VERSIONED)"
	$(PHP) scripts/validate-build.php "$(ZIP_FILE)"
	@echo "Created $(ZIP_FILE)"
	@echo "Created $(ZIP_VERSIONED)"

clean:
	rm -rf build dist vendor node_modules .phpunit.result.cache
