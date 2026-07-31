const { test, expect } = require('@playwright/test');

test.describe('EKA Visual Parity Audit', () => {
  test('Homepage visual audit loads correctly', async ({ page }) => {
    await page.goto('http://backstage.ekalexandria.org/', { waitUntil: 'networkidle' });
    await expect(page).toHaveTitle(/.*(Ελληνική Κοινότητα|Greek Community|EKA).*/i);
    await page.screenshot({ path: 'ai-work/baselines/fse_homepage_audit.png', fullPage: true });
  });

  test('Tachydromos Archive page loads correctly', async ({ page }) => {
    await page.goto('http://backstage.ekalexandria.org/αλεξανδρινός-ταχυδρόμος/', { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'ai-work/baselines/fse_tachydromos_audit.png', fullPage: true });
  });
});
