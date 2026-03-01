/**
 * Adam Module Test Helpers for Playwright
 * Re-exports plugin helpers and adds Adam-specific SELECTORS, FIXTURES, and mock/setup helpers.
 */
import { join, dirname } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import { readFileSync } from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const pluginDir = process.env.PLUGIN_DIR || process.cwd();
const finalHelpersPath = join(pluginDir, 'tests/playwright/helpers/index.mjs');
const helpersUrl = pathToFileURL(finalHelpersPath).href;
const pluginHelpers = await import(helpersUrl);

export const { auth, wordpress, newfold, a11y, utils } = pluginHelpers;
export const pluginId = process.env.PLUGIN_ID || 'bluehost';

const fixturesPath = join(__dirname, '../fixtures');

export const loadFixture = (name) => {
  return JSON.parse(readFileSync(join(fixturesPath, `${name}.json`), 'utf8'));
};

const adamItemsFixture = loadFixture('adamItems');
export const FIXTURES = {
  adamItems: adamItemsFixture,
  adamItemsEmpty: { status: 'success', response: [] },
  adamItemsMultiple: {
    ...adamItemsFixture,
    response: [ ...(adamItemsFixture.response || []), ...(adamItemsFixture.response || []) ],
  },
};

export const SELECTORS = {
  appRendered: '#wppbh-app-rendered',
  appAside: '[data-test-id="app-aside"]',
  appAsideAdamCard: '[data-test-id="app-aside-adam-card"]',
  appAsideAdamList: '.adam-aside-list',
  appAsideLoading: '.adam-aside-loading',
  appBody: '.wppbh-app-body',
};

// Match Adam items API (path or rest_route query; URL may be encoded e.g. %2F for /)
export const API_PATTERNS = {
  adamItems: /newfold-adam[\s\S]*?v1[\s\S]*?items/,
};

/**
 * Wait for the app aside to be attached and scroll it into view. Does not assert visibility
 * (avoids flaky waitFor visible in plugin utils). Use before expect(aside).toBeVisible().
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<import('@playwright/test').Locator>} The aside locator
 */
export async function ensureAsideInView(page) {
  const aside = page.locator(SELECTORS.appAside);
  await aside.waitFor({ state: 'attached', timeout: 15000 });
  await aside.scrollIntoViewIfNeeded();
  return aside;
}

/**
 * Navigate to plugin home page (where Adam aside is shown).
 * @param {import('@playwright/test').Page} page
 */
export async function navigateToHome(page) {
  await page.goto(`/wp-admin/admin.php?page=${pluginId}#/home`);
}

/**
 * Mock the WordPress REST response for newfold-adam/v1/items. Request is fulfilled in the browser;
 * no server or external Adam API is called.
 * @param {import('@playwright/test').Page} page
 * @param {Object} fixture - Must be exact WP REST shape: { response: [...] }
 */
export async function mockAdamApi(page, fixture) {
  await page.route(API_PATTERNS.adamItems, (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(fixture),
    })
  );
}

/**
 * Mock the Adam API to return an error (e.g. 502). useAdam will catch and AdamAside will render null (no cards).
 * @param {import('@playwright/test').Page} page
 * @param {number} [status=502] - HTTP status to return
 */
export async function mockAdamApiError(page, status = 502) {
  await page.route(API_PATTERNS.adamItems, (route) =>
    route.fulfill({
      status,
      contentType: 'application/json',
      body: JSON.stringify({ code: 'adam_error', message: 'Service unavailable' }),
    })
  );
}

/**
 * Dismiss WordPress "Administration email verification" screen if present.
 * wp-env often shows this on first admin access; clicking "The email is correct" continues to the dashboard.
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>} true if the verification screen was shown and dismissed
 */
export async function dismissAdminEmailVerificationIfPresent(page) {
  const heading = page.getByRole('heading', { name: 'Administration email verification' });
  try {
    await heading.waitFor({ state: 'visible', timeout: 8000 });
    await page.getByRole('button', { name: 'The email is correct' }).click();
    await page.waitForURL((url) => !url.searchParams.has('action') || url.searchParams.get('action') !== 'confirm_admin_email', { timeout: 10000 });
    await page.waitForSelector('#wpadminbar', { timeout: 5000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Run WordPress "Database Update Required" if present (e.g. after wp-env or core update).
 * Clicks "Update WordPress Database" and waits for upgrade to complete and redirect.
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>} true if the database update screen was shown and we ran the update
 */
export async function runDatabaseUpdateIfPresent(page) {
  const heading = page.getByRole('heading', { name: 'Database Update Required' });
  try {
    await heading.waitFor({ state: 'visible', timeout: 5000 });
    await page.getByRole('link', { name: 'Update WordPress Database' }).click();
    await page.waitForURL((url) => !url.pathname.includes('/upgrade.php'), { timeout: 30000 });
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Ensure we're past any WordPress post-login intercepts (verification, database update), then wait for app.
 * Call after navigateToHome when the page might show one of these screens instead of the app.
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>} true if we had to dismiss or run something (caller may re-navigate)
 */
async function dismissPostLoginIntercepts(page) {
  let changed = false;
  if (await dismissAdminEmailVerificationIfPresent(page)) changed = true;
  if (await runDatabaseUpdateIfPresent(page)) changed = true;
  return changed;
}

const DEFAULT_POST_LOGIN_INTERCEPT_MAX_ATTEMPTS = 3;

/**
 * Dismiss post-login intercepts (verification, database update) with a bounded retry loop.
 * Navigates back to home after each dismiss; stops when nothing was dismissed or max attempts reached.
 * @param {import('@playwright/test').Page} page
 * @param {{ maxAttempts?: number }} [options] - maxAttempts: max navigate+dismiss cycles (default 3)
 * @throws {Error} If intercepts are still present after maxAttempts (possible infinite intercept loop)
 */
async function dismissPostLoginInterceptsWithRetry(page, options = {}) {
  const maxAttempts = options.maxAttempts ?? DEFAULT_POST_LOGIN_INTERCEPT_MAX_ATTEMPTS;
  let attempt = 0;
  let dismissed;

  do {
    dismissed = await dismissPostLoginIntercepts(page);
    if (dismissed) {
      attempt += 1;
      if (process.env.DEBUG_ADAM_E2E) {
        console.log(`[Adam E2E] Post-login intercept dismissed (attempt ${attempt}/${maxAttempts}), re-navigating to home.`);
      }
      if (attempt >= maxAttempts) {
        throw new Error(
          `Post-login intercepts (verification/database update) still present after ${maxAttempts} attempts. ` +
          'Possible infinite intercept loop or flaky UI.'
        );
      }
      await navigateToHome(page);
    }
  } while (dismissed);
}

/**
 * Login, dismiss intercepts, set Adam mock (before first admin load so the request is always mocked),
 * navigate to home, and wait for app (and optionally Adam aside cards).
 * Handles verification and "Database Update Required" both after login and after navigateToHome.
 * @param {import('@playwright/test').Page} page
 * @param {Object} [fixture] - Adam REST response fixture (default: FIXTURES.adamItems)
 * @param {{ waitForAdamCards?: boolean }} [options] - If waitForAdamCards is false, only wait for app and aside (no cards)
 */
export async function setupAndNavigateToHome(page, fixture = FIXTURES.adamItems, options = {}) {
  const { waitForAdamCards = true } = options;
  await mockAdamApi(page, fixture);
  await auth.loginToWordPress(page);
  await dismissAdminEmailVerificationIfPresent(page);
  await runDatabaseUpdateIfPresent(page);
  await navigateToHome(page);
  await dismissPostLoginInterceptsWithRetry(page);
  await page.waitForSelector(SELECTORS.appRendered, { timeout: 15000 });
  if (waitForAdamCards) {
    await page.locator(SELECTORS.appAsideAdamCard).first().waitFor({ state: 'visible', timeout: 15000 });
  } else {
    await page.waitForSelector(SELECTORS.appAside, { state: 'attached', timeout: 15000 });
  }
}

/**
 * Same as setupAndNavigateToHome but mocks Adam API with an error (e.g. 502). Use for "API error" scenario.
 * Waits for app and aside only (no cards).
 * @param {import('@playwright/test').Page} page
 * @param {number} [status=502] - HTTP status to return
 */
export async function setupAndNavigateToHomeWithErrorMock(page, status = 502) {
  await mockAdamApiError(page, status);
  await auth.loginToWordPress(page);
  await dismissAdminEmailVerificationIfPresent(page);
  await runDatabaseUpdateIfPresent(page);
  await navigateToHome(page);
  await dismissPostLoginInterceptsWithRetry(page);
  await page.waitForSelector(SELECTORS.appRendered, { timeout: 15000 });
  await page.waitForSelector(SELECTORS.appAside, { state: 'attached', timeout: 15000 });
}
