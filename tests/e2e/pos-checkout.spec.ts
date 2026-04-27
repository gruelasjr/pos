import { expect, test, type APIRequestContext, type Page } from "@playwright/test";

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

const sellerCredentials = {
    email: "vendedor@pos.local",
    password: "secret",
};

const loginAsSeller = async (request: APIRequestContext): Promise<LoginPayload> => {
    const response = await request.post("/api/v1/auth/login", {
        data: sellerCredentials,
    });

    expect(response.ok()).toBeTruthy();
    const body = (await response.json()) as ApiEnvelope<LoginPayload>;

    expect(body.success).toBe(true);
    expect(body.data.token).toBeTruthy();

    return body.data;
};

const seedBrowserSession = async (page: Page, payload: LoginPayload): Promise<void> => {
    await page.addInitScript(({ token, user }) => {
        window.localStorage.setItem("pos-token", token);
        window.localStorage.setItem("pos-user", JSON.stringify(user));
    }, payload);
};

test("seller can create a cart, add a product, and complete checkout", async ({
    page,
    request,
}) => {
    const session = await loginAsSeller(request);
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

    await expect(page.getByText("Crear carrito")).toBeVisible();
    await page.getByLabel(/Almac/).selectOption(warehouse.id);
    await expect(page.getByRole("heading", { name: /Carrito/i })).toBeVisible();

    await page.getByLabel("Buscar producto").fill(product.sku);
    await expect(page.getByLabel("Resultados")).toContainText(product.sku);
    await page.getByLabel("Resultados").selectOption(product.id);
    await expect(
        page.getByText(product.short_description, { exact: true }).last(),
    ).toBeVisible();

    const confirmation = page.waitForEvent("dialog");
    await page.getByRole("button", { name: /Confirmar pago/i }).click();
    const dialog = await confirmation;

    expect(dialog.message()).toContain("Venta folio");
    await dialog.accept();
});
