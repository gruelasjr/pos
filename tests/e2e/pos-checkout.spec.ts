import { expect, test, type Page } from "@playwright/test";

type ApiEnvelope<T> = {
    success: boolean;
    data: T;
};

type User = {
    id: string;
    email: string;
    name: string;
};

type LoginPayload = {
    token: string;
    user: User;
};

type Warehouse = {
    id: string;
    name: string;
};

type Product = {
    id: string;
    sku: string;
    short_description: string;
};

const sellerSession: LoginPayload = {
    token: "e2e-demo-seller",
    user: {
        id: "demo-seller",
        email: "vendedor@pos.local",
        name: "Vendedor Demo",
    },
};

const seedBrowserSession = async (page: Page, payload: LoginPayload): Promise<void> => {
    await page.context().setExtraHTTPHeaders({
        Authorization: `Bearer ${payload.token}`,
    });
};

test("seller can create a cart, add a product, and complete checkout", async ({
    page,
    request,
}) => {
    const session = sellerSession;
    const headers = { Authorization: `Bearer ${session.token}` };

    const warehousesResponse = await request.get("/api/v1/warehouses?per_page=1", {
        headers,
    });
    const warehousesBody = (await warehousesResponse.json()) as ApiEnvelope<{
        items: Warehouse[];
    }>;
    const warehouse = warehousesBody.data.items[0];
    expect(warehouse?.id).toBeTruthy();

    const productsResponse = await request.get(
        `/api/v1/products?per_page=1&warehouse_id=${warehouse.id}`,
        { headers },
    );
    const productsBody = (await productsResponse.json()) as ApiEnvelope<{
        items: Product[];
    }>;
    const product = productsBody.data.items[0];
    expect(product?.sku).toBeTruthy();

    await seedBrowserSession(page, session);
    await page.goto("/pos");

    await expect(page.getByRole("heading", { name: "Cobro rápido" })).toBeVisible();
    await page.getByLabel("Caja o almacén").selectOption(warehouse.id);
    await expect(page.getByRole("heading", { name: /Carrito/i })).toBeVisible();

    await page.getByLabel("Escanear o buscar producto").fill(product.sku);
    await expect(page.getByRole("button", { name: new RegExp(product.sku) })).toBeVisible();
    await page.getByRole("button", { name: new RegExp(product.sku) }).click();
    await expect(
        page.getByText(product.short_description, { exact: true }).last(),
    ).toBeVisible();

    await page.getByRole("button", { name: /^Cobrar/ }).click();
    await page.getByLabel("Monto recibido").fill("100000");
    await page.getByRole("button", { name: "Confirmar cobro" }).click();
    await expect(page.getByRole("heading", { name: "Venta completada" })).toBeVisible();
});

test("mobile checkout keeps an accessible sticky action", async ({ page, request }) => {
    const session = sellerSession;
    await seedBrowserSession(page, session);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/pos");
    await expect(page.getByRole("heading", { name: "Cobro rápido" })).toBeVisible();
    await expect(page.getByRole("navigation", { name: "Navegación principal" })).toBeVisible();
    await expect(page.getByLabel("Escanear o buscar producto")).toBeVisible();
    await expect(page.locator("body")).not.toHaveCSS("overflow-x", "scroll");
});
