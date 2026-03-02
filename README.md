<a href="https://newfold.com/" target="_blank">
    <img src="https://newfold.com/content/experience-fragments/newfold/site-header/master/_jcr_content/root/header/logo.coreimg.svg/1621395071423/newfold-digital.svg" alt="Newfold Logo" title="Newfold Digital" align="right" 
height="42" />
</a>

# WordPress Adam Module

[![Version Number](https://img.shields.io/github/v/release/newfold-labs/wp-module-adam?color=21a0ed&labelColor=333333)](https://github.com/newfold-labs/wp-module-adam/releases)
[![License](https://img.shields.io/github/license/newfold-labs/wp-module-adam?labelColor=333333&color=666666)](https://raw.githubusercontent.com/newfold-labs/wp-module-adam/master/LICENSE)

Newfold module for Adam (Ads and More) cross-sell content in brand plugins.

## Module Responsibilities

- Exposes a REST API under `newfold-adam/v1` (e.g. `/items`) that proxies the Adam getXSell API with transient caching and sanitization.
- Registers and enqueues the Adam frontend script only when the current admin page is the brand plugin’s page (using the container’s plugin id).
- The host plugin provides a portal slot (e.g. a div with id `nfd-adam-portal`) and registers it with `NFDPortalRegistry` as `adam`.
- The Adam script subscribes to the portal and, when ready, fetches from the module REST API and renders cross-sell content (e.g. in the app aside) via `createPortal`.

## REST and cache

- **Endpoint**: `GET newfold-adam/v1/items` (no query params; container is fixed in Config).
- **Filters**: `nfd_adam_timeout` (default 30s), `nfd_adam_sslverify` (default true except in local env).
- **Transient**: Key prefix `nfd_adam_`, TTL 30 minutes.

### wp-config constants

- **NFD_ADAM_URL** – Optional. Full getXSell API URL. If defined (e.g. in wp-config.php), the module uses this **only if the URL host is in the allowed list** (see Security below). Use for QA or staging; invalid URLs are rejected and the default production URL is used instead. The action `nfd_adam_invalid_api_url_rejected` fires when a custom URL is rejected.
- **NFD_ADAM_TEST_OFFERS** – Optional. Array of test offer codes for getXSell (e.g. `['WPADMIN_LIVE_SUPPORT']`). Default is an empty array. Used for QA; can be removed later.

### Security: Adam API URL

The request payload includes context used for personalization. To avoid sending it to an untrusted host:

- **Allowed hosts** are defined in the module (see `Config::get_allowed_adam_api_hosts()`). Only **HTTPS** URLs whose host is in that list are used. If `NFD_ADAM_URL` (or a filtered URL) points to any other host, it is rejected and the default production URL is used.
- **Filter `nfd_adam_allowed_api_hosts`**: Pass an array of allowed hostnames to add custom domains (e.g. for internal QA). Use with care; only add trusted hosts.
- **Filter `nfd_adam_api_url`**: Override the final API URL. The result is validated the same way; invalid URLs are ignored.

Do not set `NFD_ADAM_URL` to a URL you do not control or trust; if the constant is compromised (e.g. via wp-config.php), the module will still reject it unless the host is in the allowlist.

## Structure

- **PHP**: `includes/` – AdamFeature, Adam, RestController, Constants. REST namespace `newfold-adam/v1`, route `/items`.
- **Frontend**: `src/` – Entry `adam.js`, components (AdamAside, AdamFragment), api, hooks, utils, styles. Build output: `build/adam/adam.min.js`, `adam.min.css`.

## Installation

### 1. Add the Newfold Satis or a path repository to your `composer.json`.

 ```bash
 composer config repositories.newfold composer https://newfold-labs.github.io/satis
 ```

Or for a local path repo (e.g. `modules/wp-module-adam`):

 ```json
 "wp-module-adam": {
   "type": "path",
   "url": "./modules/wp-module-adam",
   "options": { "symlink": true }
 }
 ```

### 2. Require the `newfold-labs/wp-module-adam` package.

 ```bash
 composer require newfold-labs/wp-module-adam
 ```

### 3. Ensure the host plugin loads Composer and the Features singleton so the Adam feature is registered and initialized.

The module registers via the `newfold/features/filter/register` filter and initializes on `plugins_loaded`.

## Testing

Playwright e2e tests live in `tests/playwright/`. They run from the brand plugin repo (see [tests/playwright/README.md](tests/playwright/README.md) for how to run them locally). CI runs them via the workflow in `.github/workflows/brand-plugin-test-playwright.yml` when the module is built and tested with the plugin.

[More on Newfold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)
[More on the Newfold Features Modules](https://github.com/newfold-labs/wp-module-features)
