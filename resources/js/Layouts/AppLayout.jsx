import { router, usePage } from "@inertiajs/react";
import { AppSidebar, AppHeader } from "../components/organisms/AppLayout";

/**
 * AppLayout Template
 *
 * Main authenticated layout with sidebar and header.
 */
const AppLayout = ({ title, children, posMode = false }) => {
    const user = usePage().props.auth?.user;

    const handleLogout = () => {
        router.post("/api/caronte/auth/logout", {}, {
            onFinish: () => router.visit("/login"),
        });
    };

    return (
        <div className={`min-h-screen app-shell text-[var(--color-text-primary)] flex ${posMode ? "pos-shell" : ""}`}>
            {!posMode && <AppSidebar user={user} onLogout={handleLogout} />}
            <main className="flex-1 flex flex-col">
                <AppHeader title={title} user={user} posMode={posMode} />
                <section className={posMode ? "pos-main" : "flex-1 p-6 overflow-auto"}>
                    {children}
                </section>
                {posMode && <nav className="mobile-pos-nav" aria-label="Navegación principal"><button className="is-active" type="button">Venta</button><button type="button" onClick={() => router.visit("/clientes")}>Clientes</button><button type="button" onClick={() => router.visit("/")}>Inicio</button></nav>}
            </main>
        </div>
    );
};

export default AppLayout;
