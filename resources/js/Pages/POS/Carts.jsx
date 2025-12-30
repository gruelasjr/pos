import { useEffect, useState } from "react";
import { Button, Card, CardBody, Input } from "../../components/atoms";
import AppLayout from "../../Layouts/AppLayout";
import useApi from "../../hooks/useApi";
import { formatCurrency } from "../../utils/formatters";
import { FormField } from "../../components/molecules";

const CartsPage = () => {
    const api = useApi();
    const [carts, setCarts] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [selectedCart, setSelectedCart] = useState(null);
    const [productQuery, setProductQuery] = useState("");
    const [products, setProducts] = useState([]);
    const [payment, setPayment] = useState({ payment_method: "cash" });

    const loadCarts = async () => {
        const response = await api.carts.list({ per_page: 50 });
        const items = response.data.items || response.data;
        setCarts(items);
        setSelectedCart((prev) => prev || items[0]);
    };

    useEffect(() => {
        const bootstrap = async () => {
            const [warehouseRes] = await Promise.all([api.warehouses.list()]);
            setWarehouses(warehouseRes.data.items || warehouseRes.data);
            await loadCarts();
        };
        bootstrap();
    }, []);

    useEffect(() => {
        const handler = setTimeout(async () => {
            if (productQuery.length === 0) return;
            const response = await api.products.list({
                query: productQuery,
                per_page: 5,
            });
            setProducts(response.data.items || response.data);
        }, 300);

        return () => clearTimeout(handler);
    }, [productQuery]);

    const createCart = async (warehouseId) => {
        const { data } = await api.carts.create({ warehouse_id: warehouseId });
        await loadCarts();
        setSelectedCart(data);
    };

    const addItem = async (productId) => {
        if (!selectedCart) return;
        const { data } = await api.carts.addItem(selectedCart.id, {
            product_id: productId,
            quantity: 1,
        });
        updateCartState(data);
    };

    const updateItem = async (itemId, quantity) => {
        const { data } = await api.carts.updateItem(selectedCart.id, itemId, {
            quantity,
        });
        updateCartState(data);
    };

    const applyDiscount = async (value) => {
        const { data } = await api.carts.update(selectedCart.id, {
            discount_total: Number(value),
        });
        updateCartState(data);
    };

    const checkout = async () => {
        const payload = {
            payment_method: payment.payment_method,
            payment_details: payment.details || null,
        };
        const { data } = await api.carts.checkout(selectedCart.id, payload);
        setPayment({ payment_method: "cash" });
        await loadCarts();
        alert(`Venta folio ${data.folio} confirmada`);
    };

    const updateCartState = (cart) => {
        setCarts((current) =>
            current.map((item) => (item.id === cart.id ? cart : item))
        );
        setSelectedCart(cart);
    };

    return (
        <AppLayout title="Cajas en mostrador">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-1 space-y-4">
                    <Card>
                        <CardBody className="space-y-3">
                            <p className="font-semibold text-[var(--color-text-primary)]">
                                Crear carrito
                            </p>
                            <FormField
                                as="select"
                                label="Almacén"
                                value=""
                                onChange={(e) => {
                                    const id = e.target.value;
                                    if (id) createCart(id);
                                }}
                            >
                                <option value="">Selecciona</option>
                                {warehouses.map((warehouse) => (
                                    <option
                                        key={warehouse.id}
                                        value={warehouse.id}
                                    >
                                        {warehouse.name}
                                    </option>
                                ))}
                            </FormField>
                        </CardBody>
                    </Card>
                    <div className="space-y-3">
                        {carts.map((cart) => (
                            <Card
                                key={cart.id}
                                className={
                                    selectedCart?.id === cart.id
                                        ? "border-[var(--color-primary-600)] border-2 cursor-pointer"
                                        : "cursor-pointer"
                                }
                                onClick={() => setSelectedCart(cart)}
                            >
                                <CardBody>
                                    <p className="text-sm text-[var(--color-text-secondary)]">
                                        {cart.visual_key}
                                    </p>
                                    <p className="text-lg font-semibold text-[var(--color-text-primary)]">
                                        {formatCurrency(cart.total_net)}
                                    </p>
                                    <p className="text-xs text-[var(--color-text-tertiary)]">
                                        {cart.items?.length || 0} artículos
                                    </p>
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                </div>
                <div className="lg:col-span-2 space-y-4">
                    {selectedCart ? (
                        <Card>
                            <CardBody className="space-y-4">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p className="text-sm text-[var(--color-text-secondary)]">
                                            {selectedCart.warehouse?.name}
                                        </p>
                                        <h2 className="text-2xl font-semibold text-[var(--color-text-primary)]">
                                            Carrito {selectedCart.visual_key}
                                        </h2>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-[var(--color-text-secondary)]">
                                            Total neto
                                        </p>
                                        <p className="text-3xl font-bold text-[var(--color-primary-600)]">
                                            {formatCurrency(
                                                selectedCart.total_net
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        label="Buscar producto"
                                        placeholder="SKU o descripción"
                                        value={productQuery}
                                        onChange={(e) =>
                                            setProductQuery(e.target.value)
                                        }
                                    />
                                    <FormField
                                        as="select"
                                        label="Resultados"
                                        value=""
                                        onChange={(e) => {
                                            const productId = e.target.value;
                                            if (productId) addItem(productId);
                                        }}
                                    >
                                        <option value="">
                                            Selecciona producto
                                        </option>
                                        {products.map((product) => (
                                            <option
                                                key={product.id}
                                                value={product.id}
                                            >
                                                {product.short_description} (
                                                {product.sku})
                                            </option>
                                        ))}
                                    </FormField>
                                </div>

                                <div className="space-y-2">
                                    {selectedCart.items?.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between bg-[var(--color-bg-secondary)] rounded-lg px-3 py-2"
                                        >
                                            <div>
                                                <p className="font-medium text-[var(--color-text-primary)]">
                                                    {
                                                        item.product
                                                            .short_description
                                                    }
                                                </p>
                                                <p className="text-xs text-[var(--color-text-secondary)]">
                                                    {formatCurrency(
                                                        item.unit_price
                                                    )}{" "}
                                                    / SKU {item.product.sku}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    className="w-20"
                                                    type="number"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        updateItem(
                                                            item.id,
                                                            Number(
                                                                e.target.value
                                                            )
                                                        )
                                                    }
                                                />
                                                <p className="text-sm font-semibold text-[var(--color-text-primary)]">
                                                    {formatCurrency(
                                                        item.subtotal
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        type="number"
                                        label="Descuento total"
                                        value={selectedCart.discount_total}
                                        onChange={(e) =>
                                            applyDiscount(e.target.value)
                                        }
                                    />
                                    <FormField
                                        as="select"
                                        label="Método de pago"
                                        value={payment.payment_method}
                                        onChange={(e) =>
                                            setPayment((prev) => ({
                                                ...prev,
                                                payment_method:
                                                    e.target.value || "cash",
                                            }))
                                        }
                                    >
                                        {[
                                            { key: "cash", label: "efectivo" },
                                            { key: "card", label: "tarjeta" },
                                            {
                                                key: "transfer",
                                                label: "transferencia",
                                            },
                                            { key: "mixed", label: "mixto" },
                                        ].map((method) => (
                                            <option
                                                key={method.key}
                                                value={method.key}
                                            >
                                                {method.label}
                                            </option>
                                        ))}
                                    </FormField>
                                </div>

                                <Button variant="success" onClick={checkout}>
                                    Confirmar pago ·{" "}
                                    {formatCurrency(selectedCart.total_neto)}
                                </Button>
                            </CardBody>
                        </Card>
                    ) : (
                        <p className="text-sm text-[var(--color-text-secondary)]">
                            Selecciona un carrito para comenzar.
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
};

export default CartsPage;
