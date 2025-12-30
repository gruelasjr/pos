import { useEffect, useState } from "react";
import { Line } from "react-chartjs-2";
import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
} from "chart.js";
import AppLayout from "../../Layouts/AppLayout";
import useApi from "../../hooks/useApi";
import { Button, Card, CardBody } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import DataTable from "../../components/organisms/DataTable";
import { formatCurrency } from "../../utils/formatters";

Chart.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend
);

const ReportsPage = () => {
    const api = useApi();
    const [warehouses, setWarehouses] = useState([]);
    const [filters, setFilters] = useState({
        warehouse_id: "",
        date: new Date().toISOString().slice(0, 10),
    });
    const [activeTab, setActiveTab] = useState("daily");
    const [daily, setDaily] = useState(null);
    const [weekly, setWeekly] = useState(null);
    const [monthly, setMonthly] = useState(null);
    const [sellerReport, setSellerReport] = useState([]);

    const loadReports = async () => {
        const params = filters.warehouse_id
            ? { warehouse_id: filters.warehouse_id }
            : {};
        const [dailyRes, weeklyRes, monthlyRes, sellerRes] = await Promise.all([
            api.reports.daily({ ...params, date: filters.date }),
            api.reports.weekly(params),
            api.reports.monthly(params),
            api.reports.bySeller(params),
        ]);
        setDaily(dailyRes.data);
        setWeekly(weeklyRes.data);
        setMonthly(monthlyRes.data);
        setSellerReport(sellerRes.data);
    };

    useEffect(() => {
        const bootstrap = async () => {
            const response = await api.warehouses.list();
            setWarehouses(response.data.items || response.data);
        };
        bootstrap();
    }, []);

    useEffect(() => {
        loadReports();
    }, [filters]);

    return (
        <AppLayout title="Reportes">
            <Card className="mb-6">
                <CardBody className="flex flex-col gap-3 md:flex-row md:items-end">
                    <FormField
                        type="date"
                        label="Fecha"
                        value={filters.date}
                        onChange={(e) =>
                            setFilters({ ...filters, date: e.target.value })
                        }
                    />
                    <FormField
                        as="select"
                        label="Almac�n"
                        value={filters.warehouse_id}
                        onChange={(e) =>
                            setFilters({
                                ...filters,
                                warehouse_id: e.target.value,
                            })
                        }
                    >
                        <option value="">Todos</option>
                        {warehouses.map((warehouse) => (
                            <option key={warehouse.id} value={warehouse.id}>
                                {warehouse.name}
                            </option>
                        ))}
                    </FormField>
                    <Button variant="ghost" onClick={loadReports}>
                        Actualizar
                    </Button>
                </CardBody>
            </Card>

            <div className="flex flex-wrap gap-2 mb-4">
                {[
                    { key: "daily", label: "Diario" },
                    { key: "weekly", label: "Semanal" },
                    { key: "monthly", label: "Mensual" },
                    { key: "seller", label: "Por vendedor" },
                ].map((tab) => (
                    <Button
                        key={tab.key}
                        size="sm"
                        variant={
                            activeTab === tab.key ? "primary" : "secondary"
                        }
                        onClick={() => setActiveTab(tab.key)}
                    >
                        {tab.label}
                    </Button>
                ))}
            </div>

            {activeTab === "daily" && (
                <Card>
                    <CardBody className="space-y-2">
                        <p className="text-sm text-[var(--color-text-secondary)]">
                            Ventas del d�a {daily?.date}
                        </p>
                        <p className="text-3xl font-semibold text-[var(--color-text-primary)]">
                            {formatCurrency(daily?.total_net || 0)}
                        </p>
                        <p className="text-sm text-[var(--color-text-secondary)]">
                            {daily?.sales || 0} tickets emitidos
                        </p>
                    </CardBody>
                </Card>
            )}

            {activeTab === "weekly" && (
                <Card>
                    <CardBody>
                        {weekly && <WeeklyChart weekly={weekly} />}
                    </CardBody>
                </Card>
            )}

            {activeTab === "monthly" && (
                <Card>
                    <CardBody>
                        <p className="text-sm text-[var(--color-text-secondary)]">
                            Mes {monthly?.month} total{" "}
                            {formatCurrency(monthly?.current?.total || 0)}
                        </p>
                    </CardBody>
                </Card>
            )}

            {activeTab === "seller" && (
                <DataTable
                    columns={[
                        { key: "seller_name", title: "Vendedor" },
                        { key: "sales", title: "Tickets" },
                        {
                            key: "total",
                            title: "Total",
                            render: (value) => formatCurrency(value),
                        },
                    ]}
                    data={sellerReport}
                    emptyMessage="Sin informacion disponible."
                />
            )}
        </AppLayout>
    );
};

const WeeklyChart = ({ weekly }) => {
    const data = {
        labels: ["Semana actual", "Semana previa"],
        datasets: [
            {
                label: "Total ventas",
                data: [weekly.current?.total || 0, weekly.previous?.total || 0],
                borderColor: "#2563eb",
                backgroundColor: "rgba(37, 99, 235, 0.2)",
            },
        ],
    };

    return <Line data={data} />;
};

export default ReportsPage;
