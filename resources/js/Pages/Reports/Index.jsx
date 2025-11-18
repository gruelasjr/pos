import { useEffect, useState } from "react";
import {
    Button,
    Card,
    CardBody,
    Input,
    Select,
    SelectItem,
    Tabs,
    Tab,
} from "@heroui/react";
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
import DataTable from "../../components/DataTable";
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
                    <Input
                        type="date"
                        label="Fecha"
                        value={filters.date}
                        onChange={(e) =>
                            setFilters({ ...filters, date: e.target.value })
                        }
                    />
                    <Select
                        label="Almacen"
                        selectedKeys={
                            filters.warehouse_id ? [filters.warehouse_id] : []
                        }
                        onSelectionChange={(keys) =>
                            setFilters({
                                ...filters,
                                warehouse_id: extractKey(keys) || "",
                            })
                        }
                    >
                        <SelectItem key="">Todos</SelectItem>
                        {warehouses.map((warehouse) => (
                            <SelectItem key={warehouse.id}>
                                {warehouse.name}
                            </SelectItem>
                        ))}
                    </Select>
                    <Button variant="ghost" onPress={loadReports}>
                        Actualizar
                    </Button>
                </CardBody>
            </Card>

            <Tabs>
                <Tab key="daily" title="Diario">
                    <Card>
                        <CardBody className="space-y-2">
                            <p className="text-sm text-slate-500">
                                Ventas del dia {daily?.date}
                            </p>
                            <p className="text-3xl font-semibold">
                                {formatCurrency(daily?.total_net || 0)}
                            </p>
                            <p className="text-sm text-slate-500">
                                {daily?.sales || 0} tickets emitidos
                            </p>
                        </CardBody>
                    </Card>
                </Tab>
                <Tab key="weekly" title="Semanal">
                    <Card>
                        <CardBody>
                            {weekly && <WeeklyChart weekly={weekly} />}
                        </CardBody>
                    </Card>
                </Tab>
                <Tab key="monthly" title="Mensual">
                    <Card>
                        <CardBody>
                            <p className="text-sm text-slate-500">
                                Mes {monthly?.month} total{" "}
                                {formatCurrency(monthly?.current?.total || 0)}
                            </p>
                        </CardBody>
                    </Card>
                </Tab>
                <Tab key="seller" title="Por vendedor">
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
                </Tab>
            </Tabs>
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

const extractKey = (keys) => {
    if (!keys) return "";
    if (typeof keys === "string") {
        return keys;
    }

    if (Array.isArray(keys)) {
        return keys[0];
    }

    const iterator = keys?.values ? keys.values() : null;
    if (iterator) {
        const next = iterator.next();
        return next.value ?? "";
    }

    return Array.from(keys || [])[0] || "";
};
