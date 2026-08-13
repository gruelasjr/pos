import { useEffect, useState } from "react";
import { router, usePage } from "@inertiajs/react";
import { AppSidebar, AppHeader } from "../components/organisms/AppLayout";

const AppLayout = ({ title, children, posMode = false }) => {
    const user = usePage().props.auth?.user;
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => typeof window !== "undefined" && window.localStorage.getItem("pos-sidebar-collapsed") === "true");
    const handleLogout = () => router.post("/api/caronte/auth/logout", {}, { onFinish: () => router.visit("/login") });

    useEffect(() => {
        window.localStorage.setItem("pos-sidebar-collapsed", String(sidebarCollapsed));
    }, [sidebarCollapsed]);

    return (
        <div className={`app-shell ${sidebarCollapsed ? "is-sidebar-collapsed" : ""} ${posMode ? "pos-shell" : ""}`}>
            <AppSidebar user={user} onLogout={handleLogout} collapsed={sidebarCollapsed} onToggle={() => setSidebarCollapsed(value => !value)}/>
            <main className="app-main">
                <AppHeader title={title} user={user}/>
                <section className={posMode ? "pos-main" : "page-main"}>{children}</section>
                <nav className="mobile-app-nav" aria-label="Navegación móvil">
                    <button onClick={() => router.visit("/")} type="button">Inicio</button>
                    <button className={posMode ? "is-active" : ""} onClick={() => router.visit("/pos")} type="button">Venta</button>
                    <button onClick={() => router.visit("/catalogo/productos")} type="button">Productos</button>
                    <button onClick={() => router.visit("/clientes")} type="button">Más</button>
                </nav>
            </main>
        </div>
    );
};

export default AppLayout;
