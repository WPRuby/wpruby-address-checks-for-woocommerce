.PHONY: install test test-unit test-integration assets build clean

PHP ?= php
COMPOSER ?= composer
NPM ?= npm

PLUGIN_SLUG := address-guard-for-woocommerce
VERSION := $(shell $(PHP) -r '$$c=file_get_contents("address-guard-for-woocommerce.php"); if (preg_match("/Version:\\s+([0-9.]+)/", $$c, $$m)) { echo $$m[1]; }')
BUILD_DIR := build/$(PLUGIN_SLUG)
DIST_DIR := dist
ZIP_FILE := $(DIST_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip

RSYNC_EXCLUDES := \
	--exclude .git \
	--exclude .github \
	--exclude node_modules \
	--exclude vendor \
	--exclude tests \
	--exclude /build \
	--exclude /dist \
	--exclude assets/admin/vue \
	--exclude phpunit.xml.dist \
	--exclude package.json \
	--exclude package-lock.json \
	--exclude vite.config.js \
	--exclude Makefile \
	--exclude composer.json \
	--exclude composer.lock \
	--exclude .phpunit.result.cache \
	--exclude .DS_Store \
	--exclude docs \
	--exclude scripts \
	--exclude '*.map'

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
	rm -rf "$(BUILD_DIR)" "$(ZIP_FILE)"
	mkdir -p "$(BUILD_DIR)" "$(DIST_DIR)"
	rsync -a $(RSYNC_EXCLUDES) ./ "$(BUILD_DIR)/"
	cd build && zip -rq "../$(ZIP_FILE)" "$(PLUGIN_SLUG)"
	$(PHP) scripts/validate-build.php "$(ZIP_FILE)"
	@echo "Created $(ZIP_FILE)"

clean:
	rm -rf build dist vendor node_modules .phpunit.result.cache
