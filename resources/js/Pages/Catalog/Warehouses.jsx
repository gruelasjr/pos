import { Button, Card, CardBody, Input, Switch } from '@heroui/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import DataTable from '../../components/DataTable';
import useApi from '../../hooks/useApi';

const WarehousesPage = () => {
    const api = useApi();
    const [warehouses, setWarehouses] = useState([]);
    const [form, setForm] = useState({ nombre: '', codigo: '', activo: true });

    const loadWarehouses = async () => {
        const { data } = await api.get('warehouses');
        setWarehouses(data);
    };

    useEffect(() => {
        loadWarehouses();
    }, []);

    const createWarehouse = async (event) => {
        event.preventDefault();
        await api.post('warehouses', form);
        setForm({ nombre: '', codigo: '', activo: true });
        loadWarehouses();
    };

    return (
        <AppLayout title="Catálogo · Almacenes">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardBody as="form" className="space-y-3" onSubmit={createWarehouse}>
                        <h2 className="text-lg font-semibold text-slate-800">Nuevo almacén</h2>
                        <Input
                            label="Nombre"
                            value={form.nombre}
                            onChange={(e) => setForm({ ...form, nombre: e.target.value })}
                            required
                        />
                        <Input
                            label="Código"
                            value={form.codigo}
                            onChange={(e) => setForm({ ...form, codigo: e.target.value })}
                            required
                        />
                        <Switch
                            isSelected={form.activo}
                            onValueChange={(value) => setForm({ ...form, activo: value })}
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
                            { key: 'nombre', title: 'Nombre' },
                            { key: 'codigo', title: 'Código' },
                            {
                                key: 'activo',
                                title: 'Estado',
                                render: (value) => (value ? 'Activo' : 'Inactivo'),
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
