import { Button, Card, CardBody, Input, Select, SelectItem, Textarea } from '@heroui/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import DataTable from '../../components/DataTable';
import useApi from '../../hooks/useApi';
import { formatCurrency } from '../../utils/formatters';

const ProductsPage = () => {
    const api = useApi();
    const [products, setProducts] = useState([]);
    const [types, setTypes] = useState([]);
    const [form, setForm] = useState({
        descripcion_corta: '',
        descripcion_larga: '',
        precio_compra: '',
        precio_venta: '',
        product_type_id: '',
        fecha_ingreso: new Date().toISOString().slice(0, 10),
    });

    const load = async () => {
        const [productRes, typeRes] = await Promise.all([
            api.get('products', { per_page: 50 }),
            api.get('product-types'),
        ]);
        setProducts(productRes.data);
        setTypes(typeRes.data);
    };

    useEffect(() => {
        load();
    }, []);

    const createProduct = async (event) => {
        event.preventDefault();
        await api.post('products', form);
        setForm({
            descripcion_corta: '',
            descripcion_larga: '',
            precio_compra: '',
            precio_venta: '',
            product_type_id: '',
            fecha_ingreso: new Date().toISOString().slice(0, 10),
        });
        load();
    };

    return (
        <AppLayout title="Catálogo · Productos">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardBody as="form" className="space-y-3" onSubmit={createProduct}>
                        <h2 className="text-lg font-semibold text-slate-800">Nuevo producto</h2>
                        <Textarea
                            label="Descripción corta"
                            maxLength={160}
                            value={form.descripcion_corta}
                            onChange={(e) => setForm({ ...form, descripcion_corta: e.target.value })}
                            required
                        />
                        <Textarea
                            label="Descripción larga"
                            value={form.descripcion_larga}
                            onChange={(e) => setForm({ ...form, descripcion_larga: e.target.value })}
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <Input
                                label="Precio compra"
                                type="number"
                                value={form.precio_compra}
                                onChange={(e) => setForm({ ...form, precio_compra: e.target.value })}
                                required
                            />
                            <Input
                                label="Precio venta"
                                type="number"
                                value={form.precio_venta}
                                onChange={(e) => setForm({ ...form, precio_venta: e.target.value })}
                                required
                            />
                        </div>
                        <Input
                            label="Fecha ingreso"
                            type="date"
                            value={form.fecha_ingreso}
                            onChange={(e) => setForm({ ...form, fecha_ingreso: e.target.value })}
                            required
                        />
                        <Select
                            label="Tipo"
                            selectedKeys={form.product_type_id ? [form.product_type_id] : []}
                            onSelectionChange={(keys) =>
                                setForm({
                                    ...form,
                                    product_type_id: extractKey(keys),
                                })
                            }
                        >
                            {types.map((type) => (
                                <SelectItem key={type.id}>{type.nombre}</SelectItem>
                            ))}
                        </Select>
                        <Button color="primary" type="submit">
                            Guardar
                        </Button>
                    </CardBody>
                </Card>
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'sku', title: 'SKU' },
                            { key: 'descripcion_corta', title: 'Descripción' },
                            {
                                key: 'precio_venta',
                                title: 'Precio venta',
                                render: (value) => formatCurrency(value),
                            },
                            {
                                key: 'type',
                                title: 'Tipo',
                                render: (_, row) => row.type?.nombre,
                            },
                            {
                                key: 'activo',
                                title: 'Estado',
                                render: (value) => (value ? 'Activo' : 'Baja'),
                            },
                        ]}
                        data={products}
                        emptyMessage="Sin productos registrados."
                    />
                </div>
            </div>
        </AppLayout>
    );
};

const extractKey = (keys) => {
    if (!keys) return '';
    if (typeof keys === 'string') return keys;
    if (Array.isArray(keys)) return keys[0];
    return Array.from(keys)[0] || '';
};

export default ProductsPage;
