import { Button, Card, CardBody, Input, Switch } from '@heroui/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import DataTable from '../../components/DataTable';
import useApi from '../../hooks/useApi';

const CustomersPage = () => {
    const api = useApi();
    const [customers, setCustomers] = useState([]);
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        accepts_marketing: true,
    });

    const load = async () => {
        const response = await api.customers.list({ per_page: 50 });
        setCustomers(response.data.items || response.data);
    };

    useEffect(() => {
        load();
    }, []);

    const createCustomer = async (event) => {
        event.preventDefault();
        await api.customers.create(form);
        setForm({
            name: '',
            email: '',
            phone: '',
            accepts_marketing: true,
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
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            required
                        />
                        <Input
                            label="Correo"
                            type="email"
                            value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                        />
                        <Input
                            label="Telefono"
                            value={form.phone}
                            onChange={(e) => setForm({ ...form, phone: e.target.value })}
                        />
                        <Switch
                            isSelected={form.accepts_marketing}
                            onValueChange={(value) => setForm({ ...form, accepts_marketing: value })}
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
                            { key: 'name', title: 'Nombre' },
                            { key: 'email', title: 'Correo' },
                            { key: 'phone', title: 'Telefono' },
                            {
                                key: 'accepts_marketing',
                                title: 'Marketing',
                                render: (value) => (value ? 'Si' : 'No'),
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
