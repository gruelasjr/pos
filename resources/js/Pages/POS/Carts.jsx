import { useEffect, useState } from "react";
import {
    Button,
    Card,
    CardBody,
    Input,
    Select,
    SelectItem,
    Tabs,
    Tab,
} from "@heroui/react";
import AppLayout from "../../Layouts/AppLayout";
import useApi from "../../hooks/useApi";
import { formatCurrency } from "../../utils/formatters";

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
                            <p className="font-semibold text-slate-700">
                                Crear carrito
                            </p>
                            <Select
                                label="Almacén"
                                placeholder="Selecciona"
                                onSelectionChange={(keys) => {
                                    const id = extractKey(keys);
                                    if (id) createCart(id);
                                }}
                            >
                                {warehouses.map((warehouse) => (
                                    <SelectItem
                                        key={warehouse.id}
                                        textValue={warehouse.name}
                                    >
                                        {warehouse.name}
                                    </SelectItem>
                                ))}
                            </Select>
                        </CardBody>
                    </Card>
                    <div className="space-y-3">
                        {carts.map((cart) => (
                            <Card
                                key={cart.id}
                                isPressable
                                onPress={() => setSelectedCart(cart)}
                                className={
                                    selectedCart?.id === cart.id
                                        ? "border-blue-500 border-2"
                                        : ""
                                }
                            >
                                <CardBody>
                                    <p className="text-sm text-slate-500">
                                        {cart.visual_key}
                                    </p>
                                    <p className="text-lg font-semibold">
                                        {formatCurrency(cart.total_net)}
                                    </p>
                                    <p className="text-xs text-slate-400">
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
                                        <p className="text-sm text-slate-500">
                                            {selectedCart.warehouse?.name}
                                        </p>
                                        <h2 className="text-2xl font-semibold">
                                            Carrito {selectedCart.visual_key}
                                        </h2>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-slate-500">
                                            Total neto
                                        </p>
                                        <p className="text-3xl font-bold text-blue-600">
                                            {formatCurrency(
                                                selectedCart.total_net
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        label="Buscar producto"
                                        placeholder="SKU o descripción"
                                        value={productQuery}
                                        onChange={(e) =>
                                            setProductQuery(e.target.value)
                                        }
                                    />
                                    <Select
                                        label="Resultados"
                                        placeholder="Selecciona producto"
                                        onSelectionChange={(keys) => {
                                            const productId = extractKey(keys);
                                            if (productId) addItem(productId);
                                        }}
                                    >
                                        {products.map((product) => (
                                            <SelectItem key={product.id}>
                                                {product.short_description} (
                                                {product.sku})
                                            </SelectItem>
                                        ))}
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    {selectedCart.items?.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {
                                                        item.product
                                                            .short_description
                                                    }
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {formatCurrency(
                                                        item.unit_price
                                                    )}{" "}
                                                    / SKU {item.product.sku}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    size="sm"
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
                                                <p className="text-sm font-semibold">
                                                    {formatCurrency(
                                                        item.subtotal
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        type="number"
                                        label="Descuento total"
                                        value={selectedCart.discount_total}
                                        onChange={(e) =>
                                            applyDiscount(e.target.value)
                                        }
                                    />
                                    <Select
                                        label="Método de pago"
                                        selectedKeys={[payment.payment_method]}
                                        onSelectionChange={(keys) =>
                                            setPayment((prev) => ({
                                                ...prev,
                                                payment_method:
                                                    extractKey(keys),
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
                                            <SelectItem key={method.key}>
                                                {method.label}
                                            </SelectItem>
                                        ))}
                                    </Select>
                                </div>

                                <Button color="success" onPress={checkout}>
                                    Confirmar pago ·{" "}
                                    {formatCurrency(selectedCart.total_neto)}
                                </Button>
                            </CardBody>
                        </Card>
                    ) : (
                        <p className="text-sm text-slate-500">
                            Selecciona un carrito para comenzar.
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
};

const extractKey = (keys) => {
    if (!keys) return null;
    if (typeof keys === "string") return keys;
    if (Array.isArray(keys)) return keys[0];
    return Array.from(keys)[0];
};

export default CartsPage;
