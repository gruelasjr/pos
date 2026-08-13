import { useState } from "react";
import Guest from "../../Layouts/Guest";
import { Alert, Button } from "../../components/atoms";
import { FormField } from "../../components/molecules";
import { createApiClient } from "../../api/client";

const api = createApiClient();

const RegisterCustomer = ({ registrationToken = "" }) => {
    const params =
        typeof window === "undefined"
            ? new URLSearchParams()
            : new URLSearchParams(window.location.search);

    const [form, setForm] = useState({
        token: registrationToken || params.get("token") || "",
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
                    <h1 className="text-2xl font-semibold font-display text-[var(--color-text-primary)]">
                        Guarda tus datos
                    </h1>
                    <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
                        Recibe tus comprobantes y agiliza tu próxima visita.
                    </p>
                </div>

                {status && (
                    <Alert variant={status.variant}>{status.message}</Alert>
                )}

                <form className="space-y-4" onSubmit={submit}>
                    {!registrationToken && !params.get("token") && <FormField label="Código de registro" value={form.token} required onChange={(event) => update("token", event.target.value)} />}
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
                        label="Teléfono"
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
                        Quiero recibir promociones y novedades. Es opcional y puedo cancelar cuando quiera.
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
