import { Head, useForm } from "@inertiajs/react";
import { Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import Authenticated from "../../Layouts/Authenticated";

const EditForm = ({ user }) => {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route("swift-auth.users.update", user.id), {
            onError: (errors) => alert(JSON.stringify(errors)),
        });
    };

    const handleCancel = () => {
        window.history.back();
    };

    return (
        <>
            <Head title="Editar usuario" />
            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-6 text-[var(--color-text-primary)]">
                    Editar usuario
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

                    <div className="flex gap-3 pt-2">
                        <Button
                            variant="ghost"
                            size="md"
                            className="flex-1"
                            onClick={handleCancel}
                        >
                            Cancelar
                        </Button>

                        <Button
                            variant="primary"
                            size="md"
                            className="flex-1"
                            disabled={processing}
                        >
                            {processing ? "Enviando..." : "Guardar"}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
};

EditForm.layout = (page) => <Authenticated>{page}</Authenticated>;

export default EditForm;
