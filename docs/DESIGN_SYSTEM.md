# Atomic Design System (ADS) - POS Application

## Overview

This project implements a modern **Atomic Design System** with comprehensive dark/light theme support, scalable component architecture, and CSS custom properties for design tokens.

---

## Table of Contents

1. [Architecture](#architecture)
2. [Design Tokens](#design-tokens)
3. [Component Layers](#component-layers)
4. [Theme System](#theme-system)
5. [Usage Guide](#usage-guide)
6. [Extending the System](#extending-the-system)
7. [Best Practices](#best-practices)

---

## Architecture

The design system follows **Atomic Design** principles:

```
Atoms (primitives)
  ↓
Molecules (simple compounds)
  ↓
Organisms (complex components)
  ↓
Layouts (page templates)
  ↓
Pages (full experiences)
```

### Directory Structure

```
resources/js/
├── components/
│   ├── atoms/              # Primitives (Button, Card, Text, etc.)
│   │   ├── Button.jsx
│   │   ├── Card.jsx
│   │   ├── Text.jsx
│   │   ├── Table.jsx
│   │   ├── FormInputs.jsx
│   │   ├── Badge.jsx
│   │   ├── Alert.jsx
│   │   └── index.js        # Barrel export
│   ├── molecules/          # Simple compounds
│   │   ├── FormField.jsx
│   │   ├── Modal.jsx
│   │   ├── ThemeToggle.jsx
│   │   ├── Navbar.jsx
│   │   ├── StatCard.jsx
│   │   └── index.js
│   └── organisms/          # Complex components
│       ├── DataTable.jsx
│       ├── AppLayout.jsx
│       └── index.js
├── context/
│   └── ThemeContext.jsx    # Global theme state
├── Layouts/
│   ├── AppLayout.jsx       # Authenticated app template
│   ├── Guest.jsx           # Public pages template
│   └── Authenticated.jsx
└── Pages/                  # All application pages
```

---

## Design Tokens

All design decisions are encoded in **CSS custom properties** defined in `resources/css/theme.css`.

### Color Palettes

Each palette has 10 shades (50–900):

```css
/* Primary (Blue) */
--color-primary-50: #eff6ff;
--color-primary-500: #3b82f6;
--color-primary-900: #1e3a8a;

/* Secondary, Success, Warning, Danger, Neutral */
/* ... similar structure ... */
```

### Semantic Tokens

Semantic tokens adapt based on light/dark mode:

```css
/* Light Mode (:root, .light) */
--color-bg-primary: #ffffff;
--color-text-primary: #111827;
--color-border-primary: #e5e7eb;

/* Dark Mode (.dark) */
--color-bg-primary: #0f172a;
--color-text-primary: #f1f5f9;
--color-border-primary: #334155;
```

### Scale Tokens

```css
/* Spacing */
--spacing-xs: 0.25rem; /* 4px */
--spacing-sm: 0.5rem; /* 8px */
--spacing-md: 1rem; /* 16px */
--spacing-lg: 1.5rem; /* 24px */
--spacing-xl: 2rem; /* 32px */
--spacing-2xl: 2.5rem; /* 40px */
--spacing-3xl: 3rem; /* 48px */
--spacing-4xl: 4rem; /* 64px */

/* Typography */
--font-size-xs: 0.75rem; /* 12px */
--font-size-sm: 0.875rem; /* 14px */
--font-size-md: 1rem; /* 16px */
--font-size-lg: 1.125rem; /* 18px */
--font-size-xl: 1.25rem; /* 20px */
--font-size-2xl: 1.5rem; /* 24px */
--font-size-3xl: 1.875rem; /* 30px */
--font-size-4xl: 2.25rem; /* 36px */

/* Border Radius */
--radius-sm: 0.375rem; /* 6px */
--radius-md: 0.5rem; /* 8px */
--radius-lg: 0.75rem; /* 12px */
--radius-xl: 1rem; /* 16px */
--radius-2xl: 1.5rem; /* 24px */
--radius-full: 9999px; /* Pill */

/* Shadows */
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.15);

/* Transitions */
--transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
--transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
--transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
```

---

## Component Layers

### Atoms

**Primitives that cannot be reduced further.**

#### Button

```jsx
import { Button } from "@/components/atoms";

<Button
    variant="primary" // primary | secondary | ghost | danger | success | warning
    size="md" // xs | sm | md | lg | xl
    disabled={false}
>
    Click me
</Button>;
```

#### Card / CardBody

```jsx
import { Card, CardBody } from "@/components/atoms";

<Card>
    <CardBody>Content</CardBody>
</Card>;
```

#### Text

```jsx
import { Text } from "@/components/atoms";

<Text
    size="md" // xs | sm | md | lg | xl | 2xl | 3xl | 4xl
    weight="medium" // normal | medium | semibold | bold
    tone="primary" // primary | secondary | tertiary | inverted | success | danger | warning | muted
    as="h2" // h1-h6 | p | span | div
>
    Flexible typography
</Text>;
```

#### FormInputs (Input, Select, Textarea)

```jsx
import { Input, Select, Textarea } from '@/components/atoms';

<Input
  type="email"
  placeholder="you@example.com"
  disabled={false}
/>

<Select>
  <option>Option 1</option>
  <option>Option 2</option>
</Select>

<Textarea placeholder="Enter text..." rows={4} />
```

#### Table (Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell)

```jsx
import {
    Table,
    TableHead,
    TableBody,
    TableRow,
    TableHeaderCell,
    TableCell,
} from "@/components/atoms";

<Table>
    <TableHead>
        <TableRow>
            <TableHeaderCell>Name</TableHeaderCell>
            <TableHeaderCell>Email</TableHeaderCell>
        </TableRow>
    </TableHead>
    <TableBody>
        <TableRow>
            <TableCell>John</TableCell>
            <TableCell>john@example.com</TableCell>
        </TableRow>
    </TableBody>
</Table>;
```

#### Badge, Divider, Spinner

```jsx
import { Badge, Divider, Spinner } from '@/components/atoms';

<Badge variant="primary">New</Badge>
<Divider />
<Spinner />
```

#### Alert

```jsx
import { Alert } from "@/components/atoms";

<Alert
    variant="info" // info | success | warning | danger
    onClose={() => {}} // Optional close handler
>
    Alert message
</Alert>;
```

### Molecules

**Simple compounds of atoms.**

#### FormField

```jsx
import { FormField } from "@/components/molecules";

<FormField
    label="Email"
    type="email"
    value={data.email}
    onChange={(e) => setData("email", e.target.value)}
    error={errors.email}
    required
/>;
```

#### Modal

```jsx
import { Modal } from "@/components/molecules";

<Modal open={isOpen} onClose={() => setIsOpen(false)}>
    <Modal.Header>Title</Modal.Header>
    <Modal.Body>Content</Modal.Body>
    <Modal.Footer>
        <Button>Close</Button>
    </Modal.Footer>
</Modal>;
```

#### ThemeToggle

```jsx
import { ThemeToggle } from "@/components/molecules";

<ThemeToggle />; // Toggle dark/light mode
```

#### Navbar

```jsx
import { Navbar } from "@/components/molecules";

<Navbar user={user} />;
```

#### StatCard

```jsx
import { StatCard } from "@/components/molecules";

<StatCard
    title="Total Sales"
    value="$12,345"
    trend="+12%"
    trendDirection="up"
/>;
```

### Organisms

**Complex components combining multiple molecules/atoms.**

#### DataTable

```jsx
import { DataTable } from "@/components/organisms";

<DataTable
    columns={[
        { key: "name", label: "Name" },
        { key: "email", label: "Email" },
    ]}
    data={users}
    onRowClick={(row) => router.visit(`/users/${row.id}`)}
/>;
```

#### AppLayout (AppSidebar, AppHeader)

```jsx
import AppLayout from "@/Layouts/AppLayout";

export default function Dashboard() {
    return (
        <AppLayout title="Dashboard">
            <div>Dashboard content</div>
        </AppLayout>
    );
}

Dashboard.layout = (page) => <AppLayout>{page}</AppLayout>;
```

---

## Theme System

### ThemeContext

Global theme state is managed via React Context.

```jsx
import { ThemeProvider } from "@/context/ThemeContext";
import { useTheme } from "@/context/ThemeContext";

// In app.jsx
<ThemeProvider>
    <App />
</ThemeProvider>;

// In any component
const { isDark, toggleTheme } = useTheme();
```

### Theme Detection

1. **localStorage**: Persisted user preference (`localStorage.theme`)
2. **System preference**: Falls back to OS dark mode setting (`prefers-color-scheme`)
3. **Manual toggle**: User can override anytime via ThemeToggle

### CSS Variable Application

```html
<!-- Light mode (default) -->
<html class="light">
    <!-- Dark mode -->
    <html class="dark">
        <!-- Component uses: -->
        <div style="background: var(--color-bg-primary)"></div>
    </html>
</html>
```

---

## Usage Guide

### Creating a New Page with ADS

```jsx
import { Head } from '@inertiajs/react';
import { Button } from '@/components/atoms';
import { FormField, StatCard } from '@/components/molecules';
import { DataTable } from '@/components/organisms';
import AppLayout from '@/Layouts/AppLayout';

const Dashboard = ({ stats, users }) => {
  return (
    <>
      <Head title="Dashboard" />

      <div className="grid grid-cols-3 gap-4 mb-6">
        {stats.map(stat => (
          <StatCard key={stat.id} {...stat} />
        ))}
      </div>

      <DataTable columns={[...]} data={users} />
    </>
  );
};

Dashboard.layout = (page) => <AppLayout title="Dashboard">{page}</AppLayout>;
export default Dashboard;
```

### Using Theme Tokens in Custom Styles

```jsx
// Always use CSS vars, never hardcoded colors
<div className="p-[var(--spacing-md)] bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
    Content
</div>;

// With Tailwind arbitrary values
className =
    "bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] shadow-[var(--shadow-md)]";
```

### Responsive Design

Use Tailwind's responsive prefixes with ADS:

```jsx
<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-[var(--spacing-lg)]">
    {items.map((item) => (
        <StatCard key={item.id} {...item} />
    ))}
</div>
```

---

## Extending the System

### Adding a New Color

1. **Update theme.css**:

```css
:root,
.light {
    --color-custom-50: #f0f9ff;
    --color-custom-100: #e0f2fe;
    /* ... 8 more shades ... */
    --color-custom-900: #0c2340;
}

.dark {
    --color-custom-50: #0c2340;
    /* ... reversed shades ... */
    --color-custom-900: #f0f9ff;
}
```

2. **Use in components**:

```jsx
<div className="bg-[var(--color-custom-500)] text-[var(--color-custom-50)]">
```

### Adding a New Atom

1. **Create `resources/js/components/atoms/NewAtom.jsx`**:

```jsx
export const NewAtom = ({ children, variant = "primary", ...props }) => (
    <div className={`bg-[var(--color-bg-${variant})] p-[var(--spacing-md)]`}>
        {children}
    </div>
);
```

2. **Export in `resources/js/components/atoms/index.js`**:

```jsx
export { NewAtom } from "./NewAtom";
```

3. **Use in molecules/pages**:

```jsx
import { NewAtom } from "@/components/atoms";
<NewAtom variant="primary">Content</NewAtom>;
```

### Adding a New Molecule

Follow same pattern:

```
molecules/
  ├── NewMolecule.jsx
  ├── index.js (add export)
```

```jsx
import { Button, Text } from "@/components/atoms";

export const NewMolecule = ({ title, action }) => (
    <div>
        <Text>{title}</Text>
        <Button>{action}</Button>
    </div>
);
```

---

## Best Practices

### ✅ DO

-   **Use theme tokens**: `bg-[var(--color-bg-primary)]` not `bg-white`
-   **Compose from atoms**: Build molecules from existing atoms
-   **Semantic HTML**: Use proper `<button>`, `<input>`, `<select>` tags
-   **Barrel exports**: Import from `@/components/atoms` not individual files
-   **Responsive**: Design mobile-first, enhance with Tailwind breakpoints
-   **Accessibility**: Include `aria-labels`, `sr-only` for screen readers
-   **Dark mode**: All components automatically adapt via CSS vars

### ❌ DON'T

-   **Hardcoded colors**: ❌ `bg-blue-500` → ✅ `bg-[var(--color-primary-500)]`
-   **Inline styles**: Use Tailwind + CSS vars, not `style={{color: '#...'}}`
-   **HeroUI/external UI**: Use ADS atoms instead
-   **Deep nesting**: Keep component tree shallow (atoms → molecules → organisms)
-   **Direct DOM manipulation**: Use React state for dynamic behavior
-   **Magic numbers**: Define in theme tokens instead

### Performance

-   **CSS vars are performant**: No JS runtime overhead, native browser support
-   **Dark mode**: Single class toggle, no re-render needed
-   **Tree shaking**: Barrel exports with named imports enable dead code elimination
-   **Code splitting**: Pages lazy-load via Inertia, components bundled per page

### Testing

```jsx
// Example: Testing Button dark mode
import { render } from "@testing-library/react";
import { Button } from "@/components/atoms";

test("Button renders with correct bg color in dark mode", () => {
    const { container } = render(
        <div className="dark">
            <Button variant="primary">Test</Button>
        </div>
    );

    // CSS vars applied via class
    expect(container.firstChild).toHaveClass("dark");
});
```

---

## Token Reference

Quick lookup for all design tokens.

| Token                    | Light        | Dark        |
| ------------------------ | ------------ | ----------- |
| `--color-bg-primary`     | `#ffffff`    | `#0f172a`   |
| `--color-bg-secondary`   | `#f9fafb`    | `#1e293b`   |
| `--color-text-primary`   | `#111827`    | `#f1f5f9`   |
| `--color-text-secondary` | `#4b5563`    | `#cbd5e1`   |
| `--color-primary-500`    | `#3b82f6`    | `#3b82f6`   |
| `--color-border-primary` | `#e5e7eb`    | `#334155`   |
| `--shadow-md`            | Light shadow | Dark shadow |

---

## Support & Contribution

For questions or improvements:

1. Check existing atoms/molecules for reusable patterns
2. Follow the atomic hierarchy (don't skip levels)
3. Always use theme tokens (CSS vars)
4. Test in both light and dark modes
5. Update this documentation when extending

**Enjoy building with ADS!** 🎨✨
