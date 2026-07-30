const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASELINES_DIR = path.join(__dirname, '..', 'ai-work', 'baselines');

if (!fs.existsSync(BASELINES_DIR)) {
  fs.mkdirSync(BASELINES_DIR, { recursive: true });
}

const PAGES = [
  { name: 'el_homepage', url: 'https://ekalexandria.org/el/' },
  { name: 'en_homepage', url: 'https://ekalexandria.org/en/' },
  { name: 'ar_homepage', url: 'https://ekalexandria.org/ar/' }
];

(async () => {
  console.log('Starting baseline screenshot extraction...');
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await page.setViewportSize({ width: 1280, height: 800 });

  for (const item of PAGES) {
    try {
      console.log(`Navigating to ${item.url}...`);
      await page.goto(item.url, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await page.waitForTimeout(2000);
      const filePath = path.join(BASELINES_DIR, `${item.name}.png`);
      await page.screenshot({ path: filePath, fullPage: false });
      console.log(`Saved screenshot: ${filePath}`);
    } catch (err) {
      console.error(`Failed to capture ${item.name} (${item.url}):`, err.message);
    }
  }

  await browser.close();
  console.log('Baseline screenshot extraction completed.');
})();
