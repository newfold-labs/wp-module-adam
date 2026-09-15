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

**Customer id (`prodInstId`).** Resolved from Hiive `GET /sites/v1/customer` and held for 12 hours. A lookup that fails after reaching the network pushes the next attempt out, starting at 5 minutes and doubling per consecutive failure up to a 12 hour ceiling, cleared as soon as one succeeds. The wait grows because the sites that never resolve are otherwise the ones that ask most often: a site Hiive has no customer record for, or one whose token was revoked, fails every time, while a site that succeeds asks twice a day. The retry schedule lives in the `nfd_adam_prod_inst_id_failure` option rather than a transient, because the failure count has to outlive the wait it produces.

Checks made before the request (wp-module-data absent, Hiive not connected, no auth token) are local, so they are not counted as failures and do not start a backoff.

The id itself is cached through the wp-module-data transient helper rather than `get_transient()`. Where a site has an `object-cache.php` drop-in, core transients live only in that cache and never fall back to the database, so a broken or non-persistent one means nothing is ever cached. The helper stores the value in the options table instead. Note the helper makes that swap based on the drop-in file being present, not on `wp_using_ext_object_cache()`, and it deliberately keeps using core transients on Bluehost Cloud, where the object cache is trusted. So on that platform this changes nothing about where the value is stored. The helper is newer than the `wp-module-data` versions this module accepts, so where it is absent the resolver falls back to the core transient functions instead of failing.

**Cross-sell items.** Stored per user in user meta. Cleared when a user with `manage_options` logs in, and otherwise kept indefinitely, so an empty result is not refetched on every page load.

There is one exception. A response built while the customer id could not be resolved is untargeted, so it records a recheck time and is treated as stale an hour later. Without that it would be served until the user next logged out, long after the id became resolvable again.

A refresh is only skipped, leaving the previous cache in place, when the Adam request itself errors or returns a non-200. A 200 carrying an error, or one of an unexpected shape, stores an empty result like any other.
