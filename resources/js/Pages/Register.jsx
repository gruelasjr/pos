import { Link, useForm, Head } from "@inertiajs/react";
import { Button } from "../components/atoms";
import { FormField } from "../components/molecules";
import Guest from "../Layouts/Guest";

const RegisterForm = () => {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("swift-auth.store"), {
            onError: (error) => {
                console.log(error);
            },
        });
    };

    return (
        <>
            <Head title="Register" />

            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-6 text-[var(--color-text-primary)]">
                    Registrarse
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField
                        label="Nombre"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        error={errors.name}
                        required
                    />

                    <FormField
                        label="Correo electrónico"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        error={errors.email}
                        required
                    />

                    <FormField
                        label="Contraseña"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        error={errors.password}
                        required
                    />

                    <FormField
                        label="Confirmar contraseña"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData("password_confirmation", e.target.value)
                        }
                        required
                    />

                    <Button
                        variant="primary"
                        size="md"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? "Registrando..." : "Registrarse"}
                    </Button>

                    <div className="text-center">
                        <span className="text-sm text-[var(--color-text-secondary)]">
                            ¿Ya tienes cuenta?{" "}
                        </span>
                        <Link
                            href={route("swift-auth.login")}
                            className="text-sm text-[var(--color-primary-600)] hover:text-[var(--color-primary-700)] font-medium"
                        >
                            Inicia sesión
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
};

RegisterForm.layout = (page) => <Guest>{page}</Guest>;

export default RegisterForm;
