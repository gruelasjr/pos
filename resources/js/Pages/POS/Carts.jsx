import { useEffect, useState } from 'react';
import {
    Button,
    Card,
    CardBody,
    Input,
    Select,
    SelectItem,
    Tabs,
    Tab,
} from '@heroui/react';
import AppLayout from '../../Layouts/AppLayout';
import useApi from '../../hooks/useApi';
import { formatCurrency } from '../../utils/formatters';

const CartsPage = () => {
    const api = useApi();
    const [carts, setCarts] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [selectedCart, setSelectedCart] = useState(null);
    const [productQuery, setProductQuery] = useState('');
    const [products, setProducts] = useState([]);
    const [payment, setPayment] = useState({ metodo_pago: 'efectivo' });

    const loadCarts = async () => {
        const { data } = await api.get('carts', { per_page: 50 });
        setCarts(data);
        setSelectedCart((prev) => prev || data[0]);
    };

    useEffect(() => {
        const bootstrap = async () => {
            const [warehouseRes] = await Promise.all([api.get('warehouses')]);
            setWarehouses(warehouseRes.data);
            await loadCarts();
        };
        bootstrap();
    }, []);

    useEffect(() => {
        const handler = setTimeout(async () => {
            if (productQuery.length === 0) return;
            const { data } = await api.get('products', { query: productQuery, per_page: 5 });
            setProducts(data);
        }, 300);

        return () => clearTimeout(handler);
    }, [productQuery]);

    const createCart = async (warehouseId) => {
        const { data } = await api.post('carts', { almacen_id: warehouseId });
        await loadCarts();
        setSelectedCart(data);
    };

    const addItem = async (productId) => {
        if (!selectedCart) return;
        const { data } = await api.post(`carts/${selectedCart.id}/items`, {
            producto_id: productId,
            cantidad: 1,
        });
        updateCartState(data);
    };

    const updateItem = async (itemId, cantidad) => {
        const { data } = await api.patch(`carts/${selectedCart.id}/items/${itemId}`, {
            cantidad,
        });
        updateCartState(data);
    };

    const applyDiscount = async (value) => {
        const { data } = await api.patch(`carts/${selectedCart.id}`, {
            descuento_total: Number(value),
        });
        updateCartState(data);
    };

    const checkout = async () => {
        const payload = {
            metodo_pago: payment.metodo_pago,
            pagos_detalle: payment.detalle || null,
        };
        const { data } = await api.post(`carts/${selectedCart.id}/checkout`, payload);
        setPayment({ metodo_pago: 'efectivo' });
        await loadCarts();
        alert(`Venta folio ${data.folio} confirmada`);
    };

    const updateCartState = (cart) => {
        setCarts((current) => current.map((item) => (item.id === cart.id ? cart : item)));
        setSelectedCart(cart);
    };

    return (
        <AppLayout title="Cajas en mostrador">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-1 space-y-4">
                    <Card>
                        <CardBody className="space-y-3">
                            <p className="font-semibold text-slate-700">Crear carrito</p>
                            <Select
                                label="Almacén"
                                placeholder="Selecciona"
                                onSelectionChange={(keys) => {
                                    const id = extractKey(keys);
                                    if (id) createCart(id);
                                }}
                            >
                                {warehouses.map((warehouse) => (
                                    <SelectItem key={warehouse.id} textValue={warehouse.nombre}>
                                        {warehouse.nombre}
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
                                    selectedCart?.id === cart.id ? 'border-blue-500 border-2' : ''
                                }
                            >
                                <CardBody>
                                    <p className="text-sm text-slate-500">{cart.clave_visual}</p>
                                    <p className="text-lg font-semibold">
                                        {formatCurrency(cart.total_neto)}
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
                                            {selectedCart.warehouse?.nombre}
                                        </p>
                                        <h2 className="text-2xl font-semibold">
                                            Carrito {selectedCart.clave_visual}
                                        </h2>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-slate-500">Total neto</p>
                                        <p className="text-3xl font-bold text-blue-600">
                                            {formatCurrency(selectedCart.total_neto)}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        label="Buscar producto"
                                        placeholder="SKU o descripción"
                                        value={productQuery}
                                        onChange={(e) => setProductQuery(e.target.value)}
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
                                                {product.descripcion_corta} ({product.sku})
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
                                                <p className="font-medium">{item.product.descripcion_corta}</p>
                                                <p className="text-xs text-slate-500">
                                                    {formatCurrency(item.precio_unitario)} / SKU {item.product.sku}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    size="sm"
                                                    type="number"
                                                    value={item.cantidad}
                                                    onChange={(e) => updateItem(item.id, Number(e.target.value))}
                                                />
                                                <p className="text-sm font-semibold">
                                                    {formatCurrency(item.subtotal)}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        type="number"
                                        label="Descuento total"
                                        value={selectedCart.descuento_total}
                                        onChange={(e) => applyDiscount(e.target.value)}
                                    />
                                    <Select
                                        label="Método de pago"
                                        selectedKeys={[payment.metodo_pago]}
                                        onSelectionChange={(keys) =>
                                            setPayment((prev) => ({
                                                ...prev,
                                                metodo_pago: extractKey(keys),
                                            }))
                                        }
                                    >
                                        {['efectivo', 'tarjeta', 'transferencia', 'mixto'].map((method) => (
                                            <SelectItem key={method}>{method}</SelectItem>
                                        ))}
                                    </Select>
                                </div>

                                <Button color="success" onPress={checkout}>
                                    Confirmar pago · {formatCurrency(selectedCart.total_neto)}
                                </Button>
                            </CardBody>
                        </Card>
                    ) : (
                        <p className="text-sm text-slate-500">Selecciona un carrito para comenzar.</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
};

const extractKey = (keys) => {
    if (!keys) return null;
    if (typeof keys === 'string') return keys;
    if (Array.isArray(keys)) return keys[0];
    return Array.from(keys)[0];
};

export default CartsPage;
