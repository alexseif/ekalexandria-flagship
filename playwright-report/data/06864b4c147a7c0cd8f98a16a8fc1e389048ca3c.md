# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual.spec.js >> Homepage Visual Regression
- Location: tests/visual.spec.js:6:1

# Error details

```
Error: page.goto: net::ERR_CERT_AUTHORITY_INVALID at https://backstage.ekalexandria.org/
Call log:
  - navigating to "https://backstage.ekalexandria.org/", waiting until "load"

```

# Test source

```ts
  1  | const { test, expect } = require('@playwright/test');
  2  | 
  3  | const LIVE_URL = 'https://ekalexandria.org/';
  4  | const STAGING_URL = 'https://backstage.ekalexandria.org/';
  5  | 
  6  | test('Homepage Visual Regression', async ({ page }) => {
  7  |   // Take a screenshot of the live site
  8  |   await page.goto(LIVE_URL);
  9  |   await page.waitForTimeout(2000); // Wait for sliders/animations
  10 |   const liveScreenshot = await page.screenshot({ fullPage: true });
  11 | 
  12 |   // Navigate to staging site
> 13 |   await page.goto(STAGING_URL);
     |              ^ Error: page.goto: net::ERR_CERT_AUTHORITY_INVALID at https://backstage.ekalexandria.org/
  14 |   await page.waitForTimeout(2000);
  15 |   
  16 |   // Compare staging screenshot against live screenshot
  17 |   // In a real test, we would compare against the baseline image
  18 |   // Here we just test that the page loads and we can take a screenshot
  19 |   await expect(page).toHaveScreenshot('homepage.png', {
  20 |     maxDiffPixels: 5000,
  21 |   });
  22 | });
  23 | 
```