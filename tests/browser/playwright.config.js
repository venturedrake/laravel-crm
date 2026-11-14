import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: '.',
    testMatch: '*.spec.js',
    workers: 1,
    timeout: 30000,
    use: { baseURL: 'http://127.0.0.1:8765', trace: 'retain-on-failure' },
    outputDir: '../../.context/browser-results',
    webServer: {
        command: 'php tests/browser/setup.php && php -S 127.0.0.1:8765 -t .context/browser-host/public tests/browser/router.php',
        cwd: '../..',
        url: 'http://127.0.0.1:8765/health',
        reuseExistingServer: false,
        timeout: 120000,
    },
});
