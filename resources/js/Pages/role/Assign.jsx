import { Head, useForm, usePage } from "@inertiajs/react";
import { Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import Authenticated from "../../Layouts/Authenticated";

const AssignForm = () => {
    const { users = [], roles = [] } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        user: "",
        role: "",
    });

    const handleUserChange = (event) => {
        setData("user", event.target.value);
    };

    const handleRoleChange = (event) => {
        setData("role", event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("swift-auth.users.role.assign"));
    };

    const handleCancel = () => {
        window.history.back();
    };

    return (
        <>
            <Head title="Asignar rol" />

            <div className="max-w-md mx-auto mt-10 bg-[var(--color-bg-primary)] p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]">
                <h2 className="text-2xl font-bold text-center mb-6 text-[var(--color-text-primary)]">
                    Asignar rol
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-primary)] mb-2">
                            Usuario
                        </label>
                        <select
                            id="users"
                            onChange={handleUserChange}
                            className="w-full border border-[var(--color-border-primary)] rounded px-3 py-2 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]"
                        >
                            <option value="">Selecciona un usuario</option>
                            {users.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-primary)] mb-2">
                            Rol
                        </label>
                        <select
                            id="roles"
                            onChange={handleRoleChange}
                            className="w-full border border-[var(--color-border-primary)] rounded px-3 py-2 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]"
                        >
                            <option value="">Selecciona un rol</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                    </div>

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

AssignForm.layout = (page) => <Authenticated>{page}</Authenticated>;

export default AssignForm;
