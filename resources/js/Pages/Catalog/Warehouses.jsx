import { Button, Card, CardBody, Input, Switch } from "@heroui/react";
import { useEffect, useState } from "react";
import AppLayout from "../../Layouts/AppLayout";
import DataTable from "../../components/DataTable";
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
                        <h2 className="text-lg font-semibold text-slate-800">
                            Nuevo almacén
                        </h2>
                        <Input
                            label="Nombre"
                            value={form.name}
                            onChange={(e) =>
                                setForm({ ...form, name: e.target.value })
                            }
                            required
                        />
                        <Input
                            label="Código"
                            value={form.code}
                            onChange={(e) =>
                                setForm({ ...form, code: e.target.value })
                            }
                            required
                        />
                        <Switch
                            isSelected={form.active}
                            onValueChange={(value) =>
                                setForm({ ...form, active: value })
                            }
                        >
                            Activo
                        </Switch>
                        <Button color="primary" type="submit">
                            Guardar
                        </Button>
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
