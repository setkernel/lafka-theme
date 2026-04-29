/* lafka-theme/tests/e2e/pdp-flow.spec.js
 * Playwright E2E for the redesigned PDP. BASE_URL env var sets the test target
 * (default http://localhost:8891).
 *
 * Run: BASE_URL=https://staging.example.com npx playwright test pdp-flow
 *
 * @since lafka-theme 5.12.0
 */
const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8891';
const PDP_URL = `${BASE_URL}/order/pizza/pepperys-classic-pizzas/meat-lovers/?cb=test`;

test.describe('PDP redesign — Meat Lovers', () => {

  test('renders all redesign sections', async ({ page }) => {
    await page.goto(PDP_URL);
    await expect(page.locator('.lafka-order-method-bar')).toBeVisible();
    await expect(page.locator('.lafka-pdp-summary__title')).toContainText('Meat Lovers');
    await expect(page.locator('.lafka-pdp-pickers')).toBeVisible();
    await expect(page.locator('.lafka-pdp-upsell')).toBeVisible();
    await expect(page.locator('.lafka-pdp-tabs')).toBeVisible();
  });

  test('CTA disabled until size + crust selected', async ({ page }) => {
    await page.goto(PDP_URL);
    const cta = page.locator('[data-lafka-add-to-cart]').first();
    await expect(cta).toBeDisabled();

    await page.locator('input[name="attribute_pa_size"][value="medium"]').check();
    await expect(cta).toBeDisabled();

    await page.locator('input[name="attribute_pa_crust"]').first().check();
    await expect(cta).toBeEnabled();
  });

  test('live price updates on size change', async ({ page }) => {
    await page.goto(PDP_URL);
    const price = page.locator('[data-lafka-live-price]');

    await page.locator('input[name="attribute_pa_size"][value="small"]').check();
    await page.locator('input[name="attribute_pa_crust"]').first().check();
    await expect(price).toContainText('12.50');

    await page.locator('input[name="attribute_pa_size"][value="large"]').check();
    await expect(price).toContainText('24.25');
  });

  test('add to cart opens drawer', async ({ page }) => {
    await page.goto(PDP_URL);
    await page.locator('input[name="attribute_pa_size"][value="medium"]').check();
    await page.locator('input[name="attribute_pa_crust"]').first().check();
    await page.locator('[data-lafka-add-to-cart]').first().click();

    const drawer = page.locator('.lafka-cart-drawer');
    await expect(drawer).toHaveAttribute('data-open', 'true', { timeout: 5000 });
    await expect(drawer.locator('.lafka-cart-drawer__items li').first()).toContainText('Meat Lovers');
  });

  test('drawer closes on Escape', async ({ page }) => {
    await page.goto(PDP_URL);
    await page.locator('input[name="attribute_pa_size"][value="medium"]').check();
    await page.locator('input[name="attribute_pa_crust"]').first().check();
    await page.locator('[data-lafka-add-to-cart]').first().click();
    await page.locator('.lafka-cart-drawer[data-open="true"]').waitFor();
    await page.keyboard.press('Escape');
    await expect(page.locator('.lafka-cart-drawer')).toHaveAttribute('data-open', 'false');
  });

  test('mobile sticky CTA visible', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(PDP_URL);
    await page.locator('input[name="attribute_pa_size"][value="medium"]').check();
    await page.locator('input[name="attribute_pa_crust"]').first().check();
    const stickyCta = page.locator('.lafka-pdp-mobile-cta');
    await expect(stickyCta).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await expect(stickyCta).toBeVisible();
  });

  test('upsell row +Add for simple product', async ({ page }) => {
    await page.goto(PDP_URL);
    const firstUpsell = page.locator('.lafka-pdp-upsell__add[data-product-type="simple"]').first();
    if (await firstUpsell.isVisible()) {
      await firstUpsell.click();
      await expect(page.locator('.lafka-cart-drawer')).toHaveAttribute('data-open', 'true', { timeout: 5000 });
    } else {
      test.skip(true, 'No simple-typed upsell card');
    }
  });
});
