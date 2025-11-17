import { Button, Card, CardBody, Input, Switch } from '@heroui/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import DataTable from '../../components/DataTable';
import useApi from '../../hooks/useApi';

const CustomersPage = () => {
    const api = useApi();
    const [customers, setCustomers] = useState([]);
    const [form, setForm] = useState({
        nombre: '',
        email: '',
        telefono: '',
        acepta_marketing: true,
    });

    const load = async () => {
        const { data } = await api.get('customers', { per_page: 50 });
        setCustomers(data);
    };

    useEffect(() => {
        load();
    }, []);

    const createCustomer = async (event) => {
        event.preventDefault();
        await api.post('customers', form);
        setForm({
            nombre: '',
            email: '',
            telefono: '',
            acepta_marketing: true,
        });
        load();
    };

    return (
        <AppLayout title="Clientes">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardBody as="form" className="space-y-3" onSubmit={createCustomer}>
                        <h2 className="text-lg font-semibold text-slate-800">Registrar cliente</h2>
                        <Input
                            label="Nombre"
                            value={form.nombre}
                            onChange={(e) => setForm({ ...form, nombre: e.target.value })}
                            required
                        />
                        <Input
                            label="Correo"
                            type="email"
                            value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                        />
                        <Input
                            label="Teléfono"
                            value={form.telefono}
                            onChange={(e) => setForm({ ...form, telefono: e.target.value })}
                        />
                        <Switch
                            isSelected={form.acepta_marketing}
                            onValueChange={(value) => setForm({ ...form, acepta_marketing: value })}
                        >
                            Acepta marketing
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
                            { key: 'email', title: 'Correo' },
                            { key: 'telefono', title: 'Teléfono' },
                            {
                                key: 'acepta_marketing',
                                title: 'Marketing',
                                render: (value) => (value ? 'Sí' : 'No'),
                            },
                        ]}
                        data={customers}
                        emptyMessage="Sin clientes registrados."
                    />
                </div>
            </div>
        </AppLayout>
    );
};

export default CustomersPage;
