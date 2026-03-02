import { test, expect } from '@playwright/test';
import {
  FIXTURES,
  SELECTORS,
  setupAndNavigateToHome,
  setupAndNavigateToHomeWithErrorMock,
  utils,
} from '../helpers/index.mjs';

test.describe('App aside (Adam module)', () => {
  test.describe.configure({ timeout: 90000 });
  let page;
  let context;

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    const baseURL = testInfo.project.use?.baseURL ?? process.env.PLAYWRIGHT_TEST_BASE_URL ?? 'http://localhost:8882';
    context = await browser.newContext({ baseURL });
    page = await context.newPage();
    await setupAndNavigateToHome(page);
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test('Is accessible', async () => {
    const aside = page.locator(SELECTORS.appAside);
    await aside.waitFor({ state: 'attached', timeout: 15000 });
    await utils.scrollIntoView(aside);
    await expect(aside).toBeVisible();
  });

  test('Aside is visible on home', async () => {
    const aside = page.locator(SELECTORS.appAside);
    await utils.scrollIntoView(aside);
    await expect(aside).toBeVisible();
  });

  test('When mocked response has items, aside shows Adam cards', async () => {
    const adamCards = page.locator(SELECTORS.appAsideAdamCard);
    await expect(adamCards.first()).toBeVisible({ timeout: 15000 });
    expect(await adamCards.count()).toBeGreaterThanOrEqual(1);
  });

  test('First card shows expected CTA content', async () => {
    const firstCard = page.locator(SELECTORS.appAsideAdamCard).first();
    await expect(firstCard).toBeVisible();
    await expect(firstCard).toContainText('Upgrade SEO');
    await expect(firstCard.getByRole('link', { name: /Upgrade SEO/i })).toBeVisible();
  });
});

test.describe('App aside – empty response', () => {
  test.describe.configure({ timeout: 90000 });
  let page;
  let context;

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    const baseURL = testInfo.project.use?.baseURL ?? process.env.PLAYWRIGHT_TEST_BASE_URL ?? 'http://localhost:8882';
    context = await browser.newContext({ baseURL });
    page = await context.newPage();
    await setupAndNavigateToHome(page, FIXTURES.adamItemsEmpty, { waitForAdamCards: false });
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test('Aside is present (empty state may have zero height)', async () => {
    await page.waitForSelector(SELECTORS.appRendered, { timeout: 15000 });
    await page.waitForSelector(SELECTORS.appAside, { state: 'attached', timeout: 20000 });
    await expect(page.locator(SELECTORS.appAside)).toHaveAttribute('data-test-id', 'app-aside');
  });

  test('No Adam cards are shown', async () => {
    await expect(page.locator(SELECTORS.appAsideAdamCard)).toHaveCount(0);
  });
});

test.describe('App aside – API error', () => {
  test.describe.configure({ timeout: 90000 });
  let page;
  let context;

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    const baseURL = testInfo.project.use?.baseURL ?? process.env.PLAYWRIGHT_TEST_BASE_URL ?? 'http://localhost:8882';
    context = await browser.newContext({ baseURL });
    page = await context.newPage();
    await setupAndNavigateToHomeWithErrorMock(page, 502);
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test('Aside is present (error state may have zero height)', async () => {
    await page.waitForSelector(SELECTORS.appRendered, { timeout: 15000 });
    await page.waitForSelector(SELECTORS.appAside, { state: 'attached', timeout: 20000 });
    await expect(page.locator(SELECTORS.appAside)).toHaveAttribute('data-test-id', 'app-aside');
  });

  test('No Adam cards are shown', async () => {
    await expect(page.locator(SELECTORS.appAsideAdamCard)).toHaveCount(0);
  });
});

test.describe('App aside – multiple items', () => {
  test.describe.configure({ timeout: 90000 });
  let page;
  let context;

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    const baseURL = testInfo.project.use?.baseURL ?? process.env.PLAYWRIGHT_TEST_BASE_URL ?? 'http://localhost:8882';
    context = await browser.newContext({ baseURL });
    page = await context.newPage();
    await setupAndNavigateToHome(page, FIXTURES.adamItemsMultiple);
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test('Aside is visible', async () => {
    const aside = page.locator(SELECTORS.appAside);
    await aside.waitFor({ state: 'attached', timeout: 15000 });
    await utils.scrollIntoView(aside);
    await expect(aside).toBeVisible();
  });

  test('Exactly two Adam cards are shown', async () => {
    const adamCards = page.locator(SELECTORS.appAsideAdamCard);
    await expect(adamCards).toHaveCount(2);
  });
});
