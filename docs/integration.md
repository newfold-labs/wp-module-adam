---
name: wp-module-adam
title: Integration
description: How the module registers and integrates.
updated: 2026-09-11
---

# Integration

The module registers with the Newfold Module Loader via bootstrap.php. The host plugin typically surfaces Adam cross-sell content. See [dependencies.md](dependencies.md).

## Caching

Two things are cached so the module does not repeat work on every request.

**Customer id (`prodInstId`).** Resolved from Hiive `GET /sites/v1/customer` and held for 12 hours. A lookup that fails after reaching the network is remembered for 15 minutes, so an unhealthy Hiive is not asked again by every request that wants the id. Checks made before the request (Hiive not connected, no auth token) are local, so they are not counted as failures.

Both values go through the wp-module-data transient helper rather than `get_transient()`. On a site with an `object-cache.php` drop-in, core transients live only in that cache and never fall back to the database, so a broken or non-persistent one means nothing is ever cached. The helper falls back to the options table in that case.

**Cross-sell items.** Stored per user in user meta and cleared when that user logs in. A refresh that fails is not stored, so the next request retries it.
