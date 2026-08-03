import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './wp-content/themes/nuvanx-medical/tests',
  timeout: 30000,
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'https://staging2.nuvanx.com',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
  ],
});