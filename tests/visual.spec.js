const { test, expect } = require('@playwright/test');

const LIVE_URL = 'https://ekalexandria.org/';
const STAGING_URL = 'https://backstage.ekalexandria.org/';

test('Homepage Visual Regression', async ({ page }) => {
  // Take a screenshot of the live site
  await page.goto(LIVE_URL);
  await page.waitForTimeout(2000); // Wait for sliders/animations
  const liveScreenshot = await page.screenshot({ fullPage: true });

  // Navigate to staging site
  await page.goto(STAGING_URL);
  await page.waitForTimeout(2000);
  
  // Compare staging screenshot against live screenshot
  // In a real test, we would compare against the baseline image
  // Here we just test that the page loads and we can take a screenshot
  await expect(page).toHaveScreenshot('homepage.png', {
    maxDiffPixels: 5000,
  });
});
