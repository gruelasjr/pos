import { Link, useForm, Head } from "@inertiajs/react";
import { Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import Guest from "../../Layouts/Guest";

const Email = () => {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("swift-auth.password.email"));
    };

    return (
        <>
            <Head title="Recover password" />

            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-4 text-[var(--color-text-primary)]">
                    Recuperar contraseña
                </h2>
                <p className="text-sm text-[var(--color-text-secondary)] mb-4 text-center">
                    Ingresa tu correo y te enviaremos un enlace para restablecer
                    tu contraseña.
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField
                        label="Correo electrónico"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        error={errors.email}
                        required
                    />

                    <Button
                        variant="primary"
                        size="md"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? "Enviando..." : "Enviar enlace"}
                    </Button>

                    <div className="text-center">
                        <Link
                            href={route("swift-auth.login")}
                            className="text-sm text-[var(--color-primary-600)] hover:text-[var(--color-primary-700)]"
                        >
                            Volver al inicio de sesión
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
};

Email.layout = (page) => <Guest>{page}</Guest>;

export default Email;
