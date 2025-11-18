import { useEffect, useState } from "react";
import AppLayout from "../../Layouts/AppLayout";
import StatCard from "../../components/StatCard";
import DataTable from "../../components/DataTable";
import useApi from "../../hooks/useApi";
import { formatCurrency, formatDate } from "../../utils/formatters";

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
                const [dailyRes, weeklyRes, sellerRes, inventoryRes] =
                    await Promise.all([
                        api.reports.daily(),
                        api.reports.weekly(),
                        api.reports.bySeller(),
                        api.inventory.list({ per_page: 5 }),
                    ]);
                setDaily(dailyRes.data);
                setWeekly(weeklyRes.data);
                setSellers(sellerRes.data);
                setAlerts(
                    (inventoryRes.data.items || inventoryRes.data).filter(
                        (item) => item.stock <= item.reorder_point
                    )
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
                <p className="text-sm text-slate-500">
                    Cargando indicadores...
                </p>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Dashboard">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label={`Ventas ${formatDate(daily?.date)}`}
                    value={formatCurrency(daily?.total_net || 0)}
                    hint={`${daily?.sales || 0} tickets`}
                />
                <StatCard
                    label="Semana (MXN)"
                    value={formatCurrency(weekly?.current?.total || 0)}
                    hint={`${weekly?.current?.sales || 0} ventas`}
                    trend={computeTrend(weekly)}
                />
                <StatCard
                    label="Inventario critico"
                    value={alerts.length}
                    hint="Productos por debajo del punto de reorden"
                />
                <StatCard
                    label="Vendedores activos"
                    value={sellers.length}
                    hint="Resumen por desempeno"
                />
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <div>
                    <h2 className="text-lg font-semibold text-slate-800 mb-3">
                        Top vendedores
                    </h2>
                    <DataTable
                        columns={[
                            { key: "seller_name", title: "Vendedor" },
                            {
                                key: "total",
                                title: "Total vendido",
                                render: (value) => formatCurrency(value),
                            },
                            { key: "sales", title: "Tickets" },
                        ]}
                        data={sellers}
                        emptyMessage="Aun no hay ventas."
                    />
                </div>
                <div>
                    <h2 className="text-lg font-semibold text-slate-800 mb-3">
                        Alertas de inventario
                    </h2>
                    <DataTable
                        columns={[
                            {
                                key: "product",
                                title: "Producto",
                                render: (_, row) =>
                                    row.product.short_description,
                            },
                            {
                                key: "warehouse",
                                title: "Almacen",
                                render: (_, row) => row.warehouse.name,
                            },
                            { key: "stock", title: "Existencias" },
                            { key: "reorder_point", title: "Punto de reorden" },
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
    if (!weekly?.current?.total || !weekly?.previous?.total) {
        return 0;
    }

    const previous = weekly.previous.total;
    if (previous === 0) {
        return 100;
    }

    return Math.round(((weekly.current.total - previous) / previous) * 100);
};

export default Dashboard;
