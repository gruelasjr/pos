import { useState } from "react";
import { Toggle } from "./Toggle";

export default {
    title: "Atoms/Toggle",
    component: Toggle,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
};

export const Default = {
    render: () => {
        const [value, setValue] = useState(false);
        return (
            <Toggle
                checked={value}
                onChange={setValue}
                label={value ? "Activo" : "Inactivo"}
                helper="Usa el switch para alternar estados"
            />
        );
    },
};

export const Disabled = {
    render: () => <Toggle checked label="Deshabilitado" disabled />,
};
