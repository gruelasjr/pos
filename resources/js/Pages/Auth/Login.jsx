import { Button, Card, CardBody, Input } from '@heroui/react';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import useAuthStore from '../../store/authStore';
import axios from 'axios';

const Login = () => {
    const setSession = useAuthStore((state) => state.setSession);
    const [form, setForm] = useState({ email: '', password: '' });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const { data } = await axios.post('/api/v1/auth/login', form);
            setSession({ token: data.data.token, user: data.data.user });
            router.visit('/');
        } catch (err) {
            setError(err.response?.data?.error?.message || 'Credenciales inválidas');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 px-4">
            <Card className="max-w-md w-full shadow-2xl">
                <CardBody as="form" className="space-y-4" onSubmit={handleSubmit}>
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-800">
                            POS Faro · Acceso
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Ingresa tus credenciales para continuar.
                        </p>
                    </div>
                    <Input
                        label="Correo"
                        type="email"
                        value={form.email}
                        onChange={(e) => setForm({ ...form, email: e.target.value })}
                        required
                    />
                    <Input
                        label="Contraseña"
                        type="password"
                        value={form.password}
                        onChange={(e) => setForm({ ...form, password: e.target.value })}
                        required
                    />
                    {error && <p className="text-sm text-rose-600">{error}</p>}
                    <Button color="primary" type="submit" isLoading={loading}>
                        Entrar
                    </Button>
                </CardBody>
            </Card>
        </div>
    );
};

export default Login;
