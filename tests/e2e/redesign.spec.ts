import { expect, test } from "@playwright/test";

const adminHeaders = { Authorization: "Bearer e2e-demo-admin" };
test.beforeEach(async ({ page }) => { await page.context().setExtraHTTPHeaders(adminHeaders); });

test("reports preserve filters and support ordered best-seller groupings", async ({ page }) => {
    await page.goto("/reportes");
    await expect(page.getByRole("heading", { name: "Reportes" })).toBeVisible();
    await page.getByRole("tab", { name: "Más vendidos" }).click();
    await page.locator(".group-add select").selectOption("tag");
    await expect(page.locator(".group-chip")).toHaveCount(2);
    await page.getByRole("button", { name: "Subir agrupación" }).last().click();
    await expect.poll(() => new URL(page.url()).searchParams.getAll("group_by[]")).toEqual(["tag", "category"]);
    await expect(page.getByRole("table", { name: "Jerarquía de productos más vendidos" })).toBeVisible();
});

test("product editor becomes a bottom sheet on a 390px viewport", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/catalogo/productos");
    await page.getByRole("button", { name: /Nuevo producto/i }).click();
    await expect(page.getByRole("dialog", { name: "Nuevo producto" })).toBeVisible();
    await expect(page.getByLabel("Nombre")).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
});

test("POS can save and reopen a sale", async ({ page, request }) => {
    const warehousePayload = await (await request.get("/api/v1/warehouses?per_page=1", { headers: adminHeaders })).json();
    const productPayload = await (await request.get(`/api/v1/products?per_page=1&warehouse_id=${warehousePayload.data.items[0].id}`, { headers: adminHeaders })).json();
    await page.goto("/pos");
    await page.getByLabel("Abrir caja").selectOption(warehousePayload.data.items[0].id);
    await page.getByLabel("Escanear o buscar producto").fill(productPayload.data.items[0].sku);
    await page.locator(".product-results").getByRole("button", { name: new RegExp(productPayload.data.items[0].short_description) }).click();
    await expect(page.getByText(productPayload.data.items[0].short_description, { exact: true }).last()).toBeVisible();
    const savedTab = page.getByRole("button", { name: /Ventas/ });
    const initialSaved = Number((await savedTab.locator("b").textContent()) || 0);
    const pausedCartResponse = page.waitForResponse(response => response.request().method() === "PATCH" && /\/api\/v1\/carts\/[^/]+$/.test(response.url()));
    await page.getByRole("button", { name: "Guardar venta" }).click();
    const pausedCart = await (await pausedCartResponse).json();
    await expect(savedTab.locator("b")).toHaveText(String(initialSaved));
    await savedTab.click();
    const pausedSale = page.locator(".saved-sales > button").filter({ hasText: pausedCart.data.visual_key });
    await expect(pausedSale).toContainText("Guardada");
    await pausedSale.click();
    await expect(page.getByText(productPayload.data.items[0].short_description, { exact: true }).last()).toBeVisible();
});
