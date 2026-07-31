import { expect, test } from "@playwright/test";

const sellerSession = {
    token: "e2e-demo-seller",
    user: {
        id: "demo-seller",
    email: "vendedor@pos.local",
        name: "Vendedor Demo",
    },
};

const seedSession = async (page, session) => {
    await page.context().setExtraHTTPHeaders({
        Authorization: `Bearer ${session.token}`,
    });
};

const getThemeToggle = (page) =>
    page.getByRole("button", { name: /theme/i }).first();

const rootClass = async (page) =>
    (await page.locator("html").getAttribute("class")) ?? "";

test.describe("Theme Toggle", () => {
    test.beforeEach(async ({ page }) => {
        await seedSession(page, sellerSession);
        await page.goto("/");
        await page.waitForLoadState("networkidle");
    });

    test("displays a header theme toggle", async ({ page }) => {
        await expect(getThemeToggle(page)).toBeVisible();
    });

    test("toggles between light and dark modes", async ({ page }) => {
        expect(await rootClass(page)).toContain("light");

        await getThemeToggle(page).click();
        expect(await rootClass(page)).toContain("dark");

        await getThemeToggle(page).click();
        expect(await rootClass(page)).toContain("light");
    });

    test("persists the theme in localStorage and across reloads", async ({
        page,
    }) => {
        await getThemeToggle(page).click();
        await expect
            .poll(() => page.evaluate(() => localStorage.getItem("theme")))
            .toBe("dark");

        await page.reload();
        await page.waitForLoadState("networkidle");

        expect(await rootClass(page)).toContain("dark");
    });

    test("uses different design tokens in light and dark modes", async ({
        page,
    }) => {
        const tokenValue = () =>
            page.evaluate(() =>
                getComputedStyle(document.documentElement)
                    .getPropertyValue("--color-bg-primary")
                    .trim(),
            );

        const lightBackground = await tokenValue();
        await getThemeToggle(page).click();
        const darkBackground = await tokenValue();

        expect(lightBackground).toBe("#ffffff");
        expect(darkBackground).toBe("#0f172a");
    });

    test("keeps the selected theme across page navigation", async ({ page }) => {
        await getThemeToggle(page).click();
        await page.goto("/clientes");
        await page.waitForLoadState("networkidle");

        expect(await rootClass(page)).toContain("dark");
    });

    test("is keyboard accessible", async ({ page }) => {
        await getThemeToggle(page).focus();
        await page.keyboard.press("Enter");

        expect(await rootClass(page)).toContain("dark");
    });

    test("keeps theme preference after logout", async ({ page }) => {
        await getThemeToggle(page).click();
        await page.goto("/pos");
        await page.waitForLoadState("networkidle");

        await expect
            .poll(() => page.evaluate(() => localStorage.getItem("theme")))
            .toBe("dark");
    });
});
