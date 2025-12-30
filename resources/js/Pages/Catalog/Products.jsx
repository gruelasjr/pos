import { Button, Card, CardBody } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import { useEffect, useState } from "react";
import AppLayout from "../../Layouts/AppLayout";
import DataTable from "../../components/organisms/DataTable";
import useApi from "../../hooks/useApi";
import { formatCurrency } from "../../utils/formatters";

const ProductsPage = () => {
    const api = useApi();
    const [products, setProducts] = useState([]);
    const [types, setTypes] = useState([]);
    const [form, setForm] = useState({
        short_description: "",
        long_description: "",
        purchase_price: "",
        sale_price: "",
        product_type_id: "",
        entry_date: new Date().toISOString().slice(0, 10),
    });

    const load = async () => {
        const [productRes, typeRes] = await Promise.all([
            api.products.list({ per_page: 50 }),
            api.productTypes.list(),
        ]);
        setProducts(productRes.data.items || productRes.data);
        setTypes(typeRes.data.items || typeRes.data);
    };

    useEffect(() => {
        load();
    }, []);

    const createProduct = async (event) => {
        event.preventDefault();
        await api.products.create(form);
        setForm({
            short_description: "",
            long_description: "",
            purchase_price: "",
            sale_price: "",
            product_type_id: "",
            entry_date: new Date().toISOString().slice(0, 10),
        });
        load();
    };

    return (
        <AppLayout title="Catálogo · Productos">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardBody
                        as="form"
                        className="space-y-3"
                        onSubmit={createProduct}
                    >
                        <h2 className="text-lg font-semibold text-[var(--color-text-primary)]">
                            Nuevo producto
                        </h2>
                        <FormField
                            as="textarea"
                            label="Descripción corta"
                            maxLength={160}
                            value={form.short_description}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    short_description: e.target.value,
                                })
                            }
                            required
                        />
                        <FormField
                            as="textarea"
                            label="Descripción larga"
                            value={form.long_description}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    long_description: e.target.value,
                                })
                            }
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <FormField
                                label="Precio compra"
                                type="number"
                                value={form.purchase_price}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        purchase_price: e.target.value,
                                    })
                                }
                                required
                            />
                            <FormField
                                label="Precio venta"
                                type="number"
                                value={form.sale_price}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        sale_price: e.target.value,
                                    })
                                }
                                required
                            />
                        </div>
                        <FormField
                            label="Fecha ingreso"
                            type="date"
                            value={form.entry_date}
                            onChange={(e) =>
                                setForm({ ...form, entry_date: e.target.value })
                            }
                            required
                        />
                        <FormField
                            as="select"
                            label="Tipo"
                            value={form.product_type_id}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    product_type_id: e.target.value,
                                })
                            }
                        >
                            <option value="">Selecciona un tipo</option>
                            {types.map((type) => (
                                <option key={type.id} value={type.id}>
                                    {type.name}
                                </option>
                            ))}
                        </FormField>
                        <Button type="submit">Guardar</Button>
                    </CardBody>
                </Card>
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: "sku", title: "SKU" },
                            { key: "short_description", title: "Descripción" },
                            {
                                key: "sale_price",
                                title: "Precio venta",
                                render: (value) => formatCurrency(value),
                            },
                            {
                                key: "type",
                                title: "Tipo",
                                render: (_, row) => row.type?.name,
                            },
                            {
                                key: "active",
                                title: "Estado",
                                render: (value) => (value ? "Activo" : "Baja"),
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

export default ProductsPage;
