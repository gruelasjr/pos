import { expect, test } from "@playwright/test";

const sellerCredentials = {
    email: "vendedor@pos.local",
    password: "secret",
};

const loginAsSeller = async (request) => {
    const response = await request.post("/api/v1/auth/login", {
        data: sellerCredentials,
    });

    expect(response.ok()).toBeTruthy();
    const body = await response.json();

    return body.data;
};

const seedSession = async (page, session) => {
    await page.addInitScript(({ token, user }) => {
        window.localStorage.setItem("pos-token", token);
        window.localStorage.setItem("pos-user", JSON.stringify(user));
    }, session);
};

const getThemeToggle = (page) =>
    page.getByRole("button", { name: /theme/i }).first();

const rootClass = async (page) =>
    (await page.locator("html").getAttribute("class")) ?? "";

test.describe("Theme Toggle", () => {
    test.beforeEach(async ({ page, request }) => {
        const session = await loginAsSeller(request);
        await seedSession(page, session);
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
        await page.evaluate(() => {
            localStorage.removeItem("pos-token");
            localStorage.removeItem("pos-user");
        });
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        await expect
            .poll(() => page.evaluate(() => localStorage.getItem("theme")))
            .toBe("dark");
    });
});
