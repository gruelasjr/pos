import { Button, Card, CardBody } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import { router } from "@inertiajs/react";
import { useState } from "react";
import useAuthStore from "../../store/authStore";
import axios from "axios";

const Login = () => {
    const setSession = useAuthStore((state) => state.setSession);
    const [form, setForm] = useState({ email: "", password: "" });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const { data } = await axios.post("/api/v1/auth/login", form);
            setSession({ token: data.data.token, user: data.data.user });
            router.visit("/");
        } catch (err) {
            setError(
                err.response?.data?.error?.message || "Credenciales inválidas"
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-[var(--color-bg-secondary)] px-4">
            <Card className="max-w-md w-full shadow-[var(--shadow-lg)]">
                <CardBody
                    as="form"
                    className="space-y-4"
                    onSubmit={handleSubmit}
                >
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--color-text-primary)]">
                            POS Faro · Acceso
                        </h1>
                        <p className="text-sm text-[var(--color-text-secondary)] mt-1">
                            Ingresa tus credenciales para continuar.
                        </p>
                    </div>
                    <FormField
                        label="Correo"
                        type="email"
                        value={form.email}
                        onChange={(e) =>
                            setForm({ ...form, email: e.target.value })
                        }
                        required
                    />
                    <FormField
                        label="Contraseña"
                        type="password"
                        value={form.password}
                        onChange={(e) =>
                            setForm({ ...form, password: e.target.value })
                        }
                        required
                    />
                    {error && (
                        <p className="text-sm text-[var(--color-danger-600)]">
                            {error}
                        </p>
                    )}
                    <Button type="submit" disabled={loading}>
                        {loading ? "Ingresando..." : "Entrar"}
                    </Button>
                </CardBody>
            </Card>
        </div>
    );
};

export default Login;
