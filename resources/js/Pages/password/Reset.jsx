import { useForm, Head } from "@inertiajs/react";
import { Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import Guest from "../../Layouts/Guest";

const Reset = ({ token, email }) => {
    const { data, setData, post, processing, errors } = useForm({
        email,
        password: "",
        password_confirmation: "",
        token,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("swift-auth.password.update"));
    };

    return (
        <>
            <Head title="Reset password" />

            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-6 text-[var(--color-text-primary)]">
                    Restablecer contraseña
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <input type="hidden" name="token" value={token} />

                    <FormField
                        label="Correo electrónico"
                        type="email"
                        value={data.email}
                        readOnly
                        disabled
                    />

                    <FormField
                        label="Nueva contraseña"
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
                        {processing
                            ? "Restableciendo..."
                            : "Restablecer contraseña"}
                    </Button>
                </form>
            </div>
        </>
    );
};

Reset.layout = (page) => <Guest>{page}</Guest>;

export default Reset;
