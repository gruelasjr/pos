import { Alert } from "./Alert";

export default {
    title: "Atoms/Alert",
    component: Alert,
    parameters: {
        layout: "centered",
    },
    tags: ["autodocs"],
    argTypes: {
        variant: {
            control: "select",
            options: ["info", "success", "warning", "danger"],
        },
    },
};

export const Info = {
    args: {
        variant: "info",
        children: "This is an informational alert.",
    },
};

export const Success = {
    args: {
        variant: "success",
        children: "Success! Your action completed.",
    },
};

export const Warning = {
    args: {
        variant: "warning",
        children: "Warning! Please review before proceeding.",
    },
};

export const Danger = {
    args: {
        variant: "danger",
        children: "Error! Something went wrong.",
    },
};

export const WithClose = {
    render: () => {
        const [open, setOpen] = React.useState(true);
        return open ? (
            <Alert variant="info" onClose={() => setOpen(false)}>
                This alert can be closed by clicking the X button.
            </Alert>
        ) : (
            <button onClick={() => setOpen(true)}>Show Alert</button>
        );
    },
};

export const AllVariants = {
    render: () => (
        <div className="flex flex-col gap-4 w-96">
            <Alert variant="info">Informational message</Alert>
            <Alert variant="success">Success message</Alert>
            <Alert variant="warning">Warning message</Alert>
            <Alert variant="danger">Danger/Error message</Alert>
        </div>
    ),
};
