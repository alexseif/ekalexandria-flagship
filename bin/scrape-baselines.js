const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASELINES_DIR = path.join(__dirname, '..', 'ai-work', 'baselines');

if (!fs.existsSync(BASELINES_DIR)) {
  fs.mkdirSync(BASELINES_DIR, { recursive: true });
}

const PAGES = [
  { name: 'el_homepage', url: 'https://ekalexandria.org/el/' },
  { name: 'en_homepage', url: 'https://ekalexandria.org/en/welcome' },
  { name: 'ar_homepage', url: 'https://ekalexandria.org/ar/%D9%85%D8%B1%D8%AD%D8%A8%D8%A7%D9%8B' }
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
      await page.goto(item.url, { waitUntil: 'networkidle', timeout: 60000 }).catch(async () => {
        console.log(`Networkidle timeout, continuing with domcontentloaded for ${item.url}...`);
        await page.goto(item.url, { waitUntil: 'domcontentloaded', timeout: 30000 });
      });

      // Additional delay for dynamic sliders & lazy loaded media
      await page.waitForTimeout(5000);

      // Scroll down to trigger any scroll-based dynamic assets, then scroll back to top
      await page.evaluate(async () => {
        await new Promise((resolve) => {
          let totalHeight = 0;
          const distance = 400;
          const timer = setInterval(() => {
            const scrollHeight = document.body.scrollHeight;
            window.scrollBy(0, distance);
            totalHeight += distance;

            if (totalHeight >= scrollHeight) {
              clearInterval(timer);
              window.scrollTo(0, 0);
              resolve();
            }
          }, 100);
        });
      });

      // Extra settling time after scrolling back up
      await page.waitForTimeout(2000);

      const filePath = path.join(BASELINES_DIR, `${item.name}.png`);
      await page.screenshot({ path: filePath, fullPage: true });
      console.log(`Saved screenshot: ${filePath}`);
    } catch (err) {
      console.error(`Failed to capture ${item.name} (${item.url}):`, err.message);
    }
  }

  await browser.close();
  console.log('Baseline screenshot extraction completed.');
})();
