/**
 * Guest Layout Template
 *
 * Layout for unauthenticated pages (login, register, etc.)
 */
export default function Guest({ children }) {
    return (
        <div className="min-h-screen bg-[var(--color-bg-primary)] flex flex-col items-center sm:justify-center sm:pt-0 pt-6">
            <div className="mt-6 w-full overflow-hidden px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                {children}
            </div>
        </div>
    );
}
