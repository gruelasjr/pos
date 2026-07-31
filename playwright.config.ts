import { defineConfig, devices } from "@playwright/test";

const port = Number(process.env.E2E_PORT ?? 8010);

export default defineConfig({
    testDir: "./tests/e2e",
    timeout: 45_000,
    expect: {
        timeout: 10_000,
    },
    fullyParallel: false,
    retries: process.env.CI ? 2 : 0,
    // The projects exercise the same seeded retail stock and folio sequence.
    // Serialize them so the matrix validates concurrency controls without
    // making device profiles consume one another's fixtures.
    workers: 1,
    reporter: process.env.CI
        ? [["list"], ["html", { open: "never" }]]
        : "list",
    use: {
        baseURL: `http://127.0.0.1:${port}`,
        screenshot: "only-on-failure",
        trace: "retain-on-failure",
        video: "retain-on-failure",
    },
    webServer: {
        command: "node scripts/e2e-server.mjs",
        env: {
            E2E_PORT: String(port),
        },
        reuseExistingServer: false,
        timeout: 120_000,
        url: `http://127.0.0.1:${port}/up`,
    },
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "pixel-7",
            use: { ...devices["Pixel 7"], browserName: "chromium" },
        },
        {
            name: "ipad-portrait",
            use: { ...devices["iPad (gen 7)"], browserName: "chromium" },
        },
        {
            name: "ipad-landscape",
            use: {
                ...devices["iPad (gen 7) landscape"],
                browserName: "chromium",
            },
        },
    ],
});
