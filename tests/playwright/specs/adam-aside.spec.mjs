import { test, expect } from '@playwright/test';
import {
  SELECTORS,
  setupAndNavigateToHome,
} from '../helpers/index.mjs';

test.describe('App aside (Adam module)', () => {
  test.describe.configure({ timeout: 90000 });
  let page;
  let context;

  test.beforeAll(async ({ browser }, testInfo) => {
    const baseURL = testInfo.project.use?.baseURL ?? process.env.PLAYWRIGHT_TEST_BASE_URL ?? 'http://localhost:8882';
    context = await browser.newContext({ baseURL });
    page = await context.newPage();
    await setupAndNavigateToHome(page);
  }, 90000);

  test.afterAll(async () => {
    await context?.close();
  });

  test('Is accessible', async () => {
    const aside = page.locator(SELECTORS.appAside);
    await aside.waitFor({ state: 'visible', timeout: 15000 });
    await expect(aside).toBeVisible();
  });

  test('Aside is visible on home', async () => {
    await expect(page.locator(SELECTORS.appAside)).toBeVisible();
  });

  test('When mocked response has items, aside shows Adam cards', async () => {
    const adamCards = page.locator(SELECTORS.appAsideAdamCard);
    const count = await adamCards.count();
    expect(count).toBeGreaterThanOrEqual(1);
    await expect(adamCards.first()).toBeVisible();
  });
});
