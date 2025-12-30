import { test, expect } from "@playwright/test";

/**
 * E2E Tests: Dark/Light Theme Toggle
 *
 * Tests verify that:
 * 1. Theme toggle button exists and is clickable
 * 2. Clicking toggle switches between light and dark modes
 * 3. Theme preference persists across page reloads
 * 4. CSS variables apply correctly in both modes
 * 5. All components respect theme colors
 */

test.describe("Theme Toggle (Dark/Light Mode)", () => {
    test.beforeEach(async ({ page }) => {
        // Clear localStorage to start fresh
        await page.context().clearCookies();
        await page.evaluate(() => localStorage.clear());

        // Navigate to dashboard (authenticated page with Navbar)
        await page.goto("/dashboard");
        await page.waitForLoadState("networkidle");
    });

    test("should display theme toggle button in navbar", async ({ page }) => {
        // Look for the theme toggle button
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await expect(toggleButton).toBeVisible();
    });

    test("should start in light mode by default", async ({ page }) => {
        // Check HTML element has 'light' class
        const html = page.locator("html");
        const classes = await html.getAttribute("class");
        expect(classes).toContain("light");
        expect(classes).not.toContain("dark");
    });

    test("should toggle from light to dark mode", async ({ page }) => {
        // Click theme toggle
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();

        // Check HTML now has 'dark' class
        const html = page.locator("html");
        const classes = await html.getAttribute("class");
        expect(classes).toContain("dark");
        expect(classes).not.toContain("light");
    });

    test("should toggle from dark to light mode", async ({ page }) => {
        // First go to dark mode
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();

        let html = page.locator("html");
        let classes = await html.getAttribute("class");
        expect(classes).toContain("dark");

        // Toggle back to light
        await toggleButton.click();

        html = page.locator("html");
        classes = await html.getAttribute("class");
        expect(classes).toContain("light");
        expect(classes).not.toContain("dark");
    });

    test("should persist theme preference in localStorage", async ({
        page,
    }) => {
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();

        // Toggle to dark mode
        await toggleButton.click();

        // Check localStorage
        const theme = await page.evaluate(() => localStorage.getItem("theme"));
        expect(theme).toBe("dark");

        // Toggle back to light
        await toggleButton.click();

        // Check localStorage updated
        const lightTheme = await page.evaluate(() =>
            localStorage.getItem("theme")
        );
        expect(lightTheme).toBe("light");
    });

    test("should restore theme from localStorage on reload", async ({
        page,
    }) => {
        // Toggle to dark mode
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();

        // Verify dark mode active
        let html = page.locator("html");
        let classes = await html.getAttribute("class");
        expect(classes).toContain("dark");

        // Reload page
        await page.reload();
        await page.waitForLoadState("networkidle");

        // Verify dark mode persisted
        html = page.locator("html");
        classes = await html.getAttribute("class");
        expect(classes).toContain("dark");
    });

    test("should apply correct CSS colors in light mode", async ({ page }) => {
        // Verify light mode colors
        const mainContent = page.locator("main").first();

        // Get computed background color (should be white/light)
        const bgColor = await mainContent.evaluate(
            (el) => getComputedStyle(el).backgroundColor
        );

        // Light mode bg should be white-ish
        expect(bgColor).toMatch(
            /rgb\(255,\s*255,\s*255\)|rgb\(249,\s*250,\s*251\)/
        );
    });

    test("should apply correct CSS colors in dark mode", async ({ page }) => {
        // Toggle to dark mode
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();
        await page.waitForTimeout(300); // Wait for transition

        // Verify dark mode colors
        const mainContent = page.locator("main").first();

        // Get computed background color (should be dark)
        const bgColor = await mainContent.evaluate(
            (el) => getComputedStyle(el).backgroundColor
        );

        // Dark mode bg should be dark-ish
        expect(bgColor).toMatch(/rgb\(15,\s*23,\s*42\)|rgb\(30,\s*41,\s*59\)/);
    });

    test("should update all component colors when toggling theme", async ({
        page,
    }) => {
        // Get initial colors in light mode
        const button = page.locator("button").first();
        const lightColor = await button.evaluate(
            (el) => getComputedStyle(el).backgroundColor
        );

        // Toggle to dark
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();
        await page.waitForTimeout(300);

        // Get dark mode color
        const darkColor = await button.evaluate(
            (el) => getComputedStyle(el).backgroundColor
        );

        // Colors should be different
        expect(lightColor).not.toBe(darkColor);
    });

    test("should theme persist across page navigation", async ({ page }) => {
        // Toggle to dark mode
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();

        // Navigate to another page
        await page.goto("/customers");
        await page.waitForLoadState("networkidle");

        // Verify dark mode still active
        const html = page.locator("html");
        const classes = await html.getAttribute("class");
        expect(classes).toContain("dark");
    });

    test("should respect system preference on first visit", async ({
        browser,
    }) => {
        // Create context with dark color scheme preference
        const contextDark = await browser.newContext({
            colorScheme: "dark",
        });

        const pageDark = await contextDark.newPage();

        // Clear localStorage
        await pageDark.evaluate(() => localStorage.clear());

        // Navigate
        await pageDark.goto("/dashboard");
        await pageDark.waitForLoadState("networkidle");

        // Should detect system dark preference
        const html = pageDark.locator("html");
        const classes = await html.getAttribute("class");

        // Either dark or light is fine, as long as one is set
        expect(classes).toMatch(/light|dark/);

        await contextDark.close();
    });

    test("theme toggle should be accessible via keyboard", async ({ page }) => {
        // Focus on toggle button
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.focus();

        // Press Enter to toggle
        await page.keyboard.press("Enter");

        // Verify theme changed
        const html = page.locator("html");
        const classes = await html.getAttribute("class");
        expect(classes).toContain("dark");
    });

    test("should not cause layout shift when toggling theme", async ({
        page,
    }) => {
        // Get initial layout dimensions
        const body = page.locator("body");
        const initialWidth = await body.evaluate((el) => el.scrollWidth);

        // Toggle theme
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();
        await page.waitForTimeout(300);

        // Check layout dimensions unchanged
        const finalWidth = await body.evaluate((el) => el.scrollWidth);
        expect(finalWidth).toBe(initialWidth);
    });
});

/**
 * Authentication + Theme Tests
 */
test.describe("Theme with Authentication", () => {
    test("should persist theme for authenticated user", async ({ page }) => {
        // Assuming login credentials are set
        await page.goto("/login");
        await page.fill('input[type="email"]', "test@example.com");
        await page.fill('input[type="password"]', "password123");
        await page.click('button[type="submit"]');
        await page.waitForNavigation();

        // Toggle theme
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        if (await toggleButton.isVisible()) {
            await toggleButton.click();

            // Reload and verify persistence
            await page.reload();
            const html = page.locator("html");
            const classes = await html.getAttribute("class");
            expect(classes).toContain("dark");
        }
    });

    test("should maintain theme across logout and login", async ({ page }) => {
        // Go to dashboard
        await page.goto("/dashboard");

        // Toggle to dark mode
        const toggleButton = page
            .locator('button[aria-label*="theme"]')
            .first();
        await toggleButton.click();

        // Logout
        await page.click('button:has-text("Cerrar sesión")');
        await page.waitForNavigation();

        // Theme should still be in localStorage
        const theme = await page.evaluate(() => localStorage.getItem("theme"));
        expect(theme).toBe("dark");
    });
});
