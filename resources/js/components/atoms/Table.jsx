import clsx from "clsx";

/**
 * Table Component
 *
 * Base table wrapper with theme support.
 */
export const Table = ({ className, children, ...props }) => (
    <table
        className={clsx(
            "min-w-full divide-y divide-[var(--color-border-primary)]",
            className
        )}
        {...props}
    >
        {children}
    </table>
);

/**
 * TableHead Component
 *
 * Table header wrapper.
 */
export const TableHead = ({ className, children, ...props }) => (
    <thead
        className={clsx("bg-[var(--color-bg-secondary)]", className)}
        {...props}
    >
        {children}
    </thead>
);

/**
 * TableBody Component
 *
 * Table body wrapper.
 */
export const TableBody = ({ className, children, ...props }) => (
    <tbody
        className={clsx(
            "divide-y divide-[var(--color-border-primary)]",
            className
        )}
        {...props}
    >
        {children}
    </tbody>
);

/**
 * TableRow Component
 *
 * Table row with hover and transition effects.
 */
export const TableRow = ({ className, children, ...props }) => (
    <tr
        className={clsx(
            "hover:bg-[var(--color-bg-tertiary)] transition-colors",
            className
        )}
        {...props}
    >
        {children}
    </tr>
);

/**
 * TableHeaderCell Component
 *
 * Styled table header cell.
 */
export const TableHeaderCell = ({ className, children, ...props }) => (
    <th
        className={clsx(
            "px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--color-text-secondary)]",
            className
        )}
        {...props}
    >
        {children}
    </th>
);

/**
 * TableCell Component
 *
 * Styled table data cell.
 */
export const TableCell = ({ className, children, ...props }) => (
    <td
        className={clsx(
            "px-6 py-4 text-sm text-[var(--color-text-primary)]",
            className
        )}
        {...props}
    >
        {children}
    </td>
);
