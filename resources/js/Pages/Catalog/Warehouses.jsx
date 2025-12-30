import { Button, Card, CardBody, Toggle } from "../../components/atoms";
import { useEffect, useState } from "react";
import AppLayout from "../../Layouts/AppLayout";
import DataTable from "../../components/organisms/DataTable";
import { FormField } from "../../components/molecules";
import useApi from "../../hooks/useApi";

const WarehousesPage = () => {
    const api = useApi();
    const [warehouses, setWarehouses] = useState([]);
    const [form, setForm] = useState({ name: "", code: "", active: true });

    const loadWarehouses = async () => {
        const response = await api.warehouses.list();
        setWarehouses(response.data.items || response.data);
    };

    useEffect(() => {
        loadWarehouses();
    }, []);

    const createWarehouse = async (event) => {
        event.preventDefault();
        await api.warehouses.create(form);
        setForm({ name: "", code: "", active: true });
        loadWarehouses();
    };

    return (
        <AppLayout title="Catálogo · Almacenes">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardBody
                        as="form"
                        className="space-y-3"
                        onSubmit={createWarehouse}
                    >
                        <h2 className="text-lg font-semibold text-[var(--color-text-primary)]">
                            Nuevo almacén
                        </h2>
                        <FormField
                            label="Nombre"
                            value={form.name}
                            onChange={(e) =>
                                setForm({ ...form, name: e.target.value })
                            }
                            required
                        />
                        <FormField
                            label="Código"
                            value={form.code}
                            onChange={(e) =>
                                setForm({ ...form, code: e.target.value })
                            }
                            required
                        />
                        <Toggle
                            checked={form.active}
                            onChange={(value) =>
                                setForm({ ...form, active: value })
                            }
                            label="Activo"
                        />
                        <Button type="submit">Guardar</Button>
                    </CardBody>
                </Card>
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: "name", title: "Nombre" },
                            { key: "code", title: "Código" },
                            {
                                key: "active",
                                title: "Estado",
                                render: (value) =>
                                    value ? "Activo" : "Inactivo",
                            },
                        ]}
                        data={warehouses}
                        emptyMessage="Sin almacenes registrados."
                    />
                </div>
            </div>
        </AppLayout>
    );
};

export default WarehousesPage;
