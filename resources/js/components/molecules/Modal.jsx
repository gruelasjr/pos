import clsx from "clsx";
import { Card, CardBody } from "../atoms/Card";
import { Text } from "../atoms/Text";

/**
 * Modal Molecule
 *
 * Overlay dialog component.
 */
export const Modal = ({
    isOpen,
    onClose,
    title,
    children,
    footer,
    size = "md",
    className,
}) => {
    if (!isOpen) return null;

    const sizes = {
        sm: "max-w-sm",
        md: "max-w-md",
        lg: "max-w-lg",
        xl: "max-w-xl",
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Overlay */}
            <div
                className="absolute inset-0 bg-[var(--color-bg-overlay)] transition-opacity"
                onClick={onClose}
            />

            {/* Modal */}
            <Card
                className={clsx(
                    "relative mx-4",
                    sizes[size] ?? sizes.md,
                    className
                )}
            >
                {/* Header */}
                {title && (
                    <div className="flex items-center justify-between border-b border-[var(--color-border-primary)] px-6 py-4">
                        <Text as="h2" size="lg" weight="semibold">
                            {title}
                        </Text>
                        <button
                            onClick={onClose}
                            className="text-[var(--color-text-tertiary)] hover:text-[var(--color-text-primary)] transition-colors"
                            aria-label="Close modal"
                        >
                            ✕
                        </button>
                    </div>
                )}

                {/* Content */}
                <CardBody>{children}</CardBody>

                {/* Footer */}
                {footer && (
                    <div className="flex items-center justify-end gap-3 border-t border-[var(--color-border-primary)] px-6 py-4">
                        {footer}
                    </div>
                )}
            </Card>
        </div>
    );
};
