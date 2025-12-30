import { Link, useForm, Head } from "@inertiajs/react";
import { Button } from "../components/atoms";
import { FormField } from "../components/molecules";
import Guest from "../Layouts/Guest";

const LoginForm = () => {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("swift-auth.login"));
    };

    return (
        <>
            <Head title="Login" />

            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] px-6 py-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-6 text-[var(--color-text-primary)]">
                    Iniciar sesión
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField
                        label="Correo electrónico"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        error={errors.email}
                        required
                        autoFocus
                    />

                    <FormField
                        label="Contraseña"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        error={errors.password}
                        required
                    />

                    <Button
                        variant="primary"
                        size="md"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? "Cargando..." : "Iniciar sesión"}
                    </Button>

                    <div className="text-center">
                        <Link
                            href={route("swift-auth.password.request.form")}
                            className="text-sm text-[var(--color-primary-600)] hover:text-[var(--color-primary-700)]"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                </form>

                <div className="text-center mt-6 pt-6 border-t border-[var(--color-border-primary)]">
                    <span className="text-sm text-[var(--color-text-secondary)]">
                        ¿No tienes cuenta?{" "}
                    </span>
                    <Link
                        href={route("swift-auth.public.register")}
                        className="text-sm text-[var(--color-primary-600)] hover:text-[var(--color-primary-700)] font-medium"
                    >
                        Regístrate
                    </Link>
                </div>
            </div>
        </>
    );
};

LoginForm.layout = (page) => <Guest>{page}</Guest>;

export default LoginForm;
