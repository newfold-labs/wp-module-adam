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

- **NFD_ADAM_URL** – Optional. Full getXSell API URL. If defined (e.g. in wp-config.php), the module uses this instead of the default production URL. Use for QA or staging (e.g. `https://global-nfd-nfd-adserver-bh.apps.atlanta1.newfoldmb.com/api/v1/getXSell`).
- **NFD_ADAM_TEST_OFFERS** – Optional. Array of test offer codes for getXSell (e.g. `['WPADMIN_LIVE_SUPPORT']`). Default is an empty array. Used for QA; can be removed later.

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

[More on Newfold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)
[More on the Newfold Features Modules](https://github.com/newfold-labs/wp-module-features)
