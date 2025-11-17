import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import StatCard from '../../components/StatCard';
import DataTable from '../../components/DataTable';
import useApi from '../../hooks/useApi';
import { formatCurrency, formatDate } from '../../utils/formatters';

const Dashboard = () => {
    const api = useApi();
    const [loading, setLoading] = useState(true);
    const [daily, setDaily] = useState(null);
    const [weekly, setWeekly] = useState(null);
    const [sellers, setSellers] = useState([]);
    const [alerts, setAlerts] = useState([]);

    useEffect(() => {
        const load = async () => {
            try {
                const [dailyRes, weeklyRes, sellerRes, inventoryRes] = await Promise.all([
                    api.get('reports/daily'),
                    api.get('reports/weekly'),
                    api.get('reports/by-seller'),
                    api.get('inventory', { per_page: 5 }),
                ]);
                setDaily(dailyRes.data);
                setWeekly(weeklyRes.data);
                setSellers(sellerRes.data);
                setAlerts(
                    inventoryRes.data.filter((item) => item.existencias <= item.punto_reorden),
                );
            } finally {
                setLoading(false);
            }
        };

        load();
    }, []);

    if (loading) {
        return (
            <AppLayout title="Dashboard">
                <p className="text-sm text-slate-500">Cargando indicadores...</p>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Dashboard">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label={`Ventas ${formatDate(daily?.fecha)}`}
                    value={formatCurrency(daily?.total_neto || 0)}
                    hint={`${daily?.ventas || 0} tickets`}
                />
                <StatCard
                    label="Semana (MXN)"
                    value={formatCurrency(weekly?.actual?.total || 0)}
                    hint={`${weekly?.actual?.ventas || 0} ventas`}
                    trend={computeTrend(weekly)}
                />
                <StatCard
                    label="Inventario crítico"
                    value={alerts.length}
                    hint="Productos por debajo del punto de reorden"
                />
                <StatCard
                    label="Vendedores activos"
                    value={sellers.length}
                    hint="Resumen por desempeño"
                />
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <div>
                    <h2 className="text-lg font-semibold text-slate-800 mb-3">Top vendedores</h2>
                    <DataTable
                        columns={[
                            { key: 'seller_name', title: 'Vendedor' },
                            {
                                key: 'total',
                                title: 'Total vendido',
                                render: (value) => formatCurrency(value),
                            },
                            { key: 'ventas', title: 'Tickets' },
                        ]}
                        data={sellers}
                        emptyMessage="Aún no hay ventas."
                    />
                </div>
                <div>
                    <h2 className="text-lg font-semibold text-slate-800 mb-3">
                        Alertas de inventario
                    </h2>
                    <DataTable
                        columns={[
                            {
                                key: 'product',
                                title: 'Producto',
                                render: (_, row) => row.product.descripcion_corta,
                            },
                            {
                                key: 'warehouse',
                                title: 'Almacén',
                                render: (_, row) => row.warehouse.nombre,
                            },
                            { key: 'existencias', title: 'Existencias' },
                            { key: 'punto_reorden', title: 'Punto de reorden' },
                        ]}
                        data={alerts}
                        emptyMessage="Sin alertas activas."
                    />
                </div>
            </div>
        </AppLayout>
    );
};

const computeTrend = (weekly) => {
    if (!weekly?.actual?.total || !weekly?.anterior?.total) {
        return 0;
    }

    const previous = weekly.anterior.total;
    if (previous === 0) {
        return 100;
    }

    return Math.round(((weekly.actual.total - previous) / previous) * 100);
};

export default Dashboard;
