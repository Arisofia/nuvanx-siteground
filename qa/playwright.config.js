const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: '.',
	testMatch: 'ticket43-v3-home.spec.js',
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	retries: 0,
	reporter: [['list']],
	use: {
		baseURL: process.env.STAGING_BASE_URL,
		httpCredentials: {
			username: process.env.STAGING_BASIC_USER,
			password: process.env.STAGING_BASIC_PASSWORD,
		},
		trace: 'off',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
});