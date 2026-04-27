import { useState } from "react";
import Guest from "../../Layouts/Guest";
import { Alert, Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import { createApiClient } from "../../api/client";

const api = createApiClient();

const RegisterCustomer = () => {
    const params =
        typeof window === "undefined"
            ? new URLSearchParams()
            : new URLSearchParams(window.location.search);

    const [form, setForm] = useState({
        token: params.get("token") || "",
        name: "",
        email: "",
        phone: "",
        accepts_marketing: false,
    });
    const [status, setStatus] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    const update = (key, value) => {
        setForm((current) => ({ ...current, [key]: value }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setStatus(null);

        try {
            await api.customers.register(form);
            setStatus({
                variant: "success",
                message: "Tus datos quedaron registrados.",
            });
        } catch (error) {
            setStatus({
                variant: "danger",
                message:
                    error?.response?.data?.error?.message ||
                    "No pudimos validar este enlace.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Guest>
            <div className="space-y-5">
                <div>
                    <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
                        Registro de cliente
                    </h1>
                    <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
                        Completa tus datos para asociarlos a tu compra.
                    </p>
                </div>

                {status && (
                    <Alert variant={status.variant}>{status.message}</Alert>
                )}

                <form className="space-y-4" onSubmit={submit}>
                    <FormField
                        label="Token"
                        value={form.token}
                        required
                        onChange={(event) =>
                            update("token", event.target.value)
                        }
                    />
                    <FormField
                        label="Nombre"
                        value={form.name}
                        required
                        onChange={(event) =>
                            update("name", event.target.value)
                        }
                    />
                    <FormField
                        label="Correo"
                        type="email"
                        value={form.email}
                        onChange={(event) =>
                            update("email", event.target.value)
                        }
                    />
                    <FormField
                        label="Telefono"
                        value={form.phone}
                        onChange={(event) =>
                            update("phone", event.target.value)
                        }
                    />
                    <label className="flex items-start gap-3 text-sm text-[var(--color-text-secondary)]">
                        <input
                            type="checkbox"
                            className="mt-1"
                            checked={form.accepts_marketing}
                            onChange={(event) =>
                                update(
                                    "accepts_marketing",
                                    event.target.checked
                                )
                            }
                        />
                        Acepto recibir promociones y avisos de la tienda.
                    </label>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={submitting}
                    >
                        {submitting ? "Registrando..." : "Registrar datos"}
                    </Button>
                </form>
            </div>
        </Guest>
    );
};

export default RegisterCustomer;
