import { router } from "@inertiajs/react";
import useAuthStore from "../store/authStore";
import { AppSidebar, AppHeader } from "../components/organisms/AppLayout";

/**
 * AppLayout Template
 *
 * Main authenticated layout with sidebar and header.
 */
const AppLayout = ({ title, children }) => {
    const { user, token, logout } = useAuthStore();

    const handleLogout = () => {
        logout();
        router.visit("/login");
    };

    if (!token) {
        router.visit("/login");
        return null;
    }

    return (
        <div className="min-h-screen bg-[var(--color-bg-primary)] flex">
            <AppSidebar user={user} token={token} onLogout={handleLogout} />
            <main className="flex-1 flex flex-col">
                <AppHeader title={title} user={user} />
                <section className="flex-1 p-6 overflow-auto">
                    {children}
                </section>
            </main>
        </div>
    );
};

export default AppLayout;
