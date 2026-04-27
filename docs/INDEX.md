# POS Faro — Complete Documentation

Welcome to the POS Faro documentation. All project documentation is consolidated here with no duplication.

---

## 📚 Quick Navigation

### For Everyone

- **[README.md](../README.md)** - Project overview, quick start, tech stack (start here!)
- **[USER_GUIDE.md](./USER_GUIDE.md)** - How to use the POS application (login, POS, reports, troubleshooting)

### For Developers

- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - System design, tech stack details, API architecture, deployment
- **[DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)** - Component library (atoms, molecules, organisms), theme system, usage guide
- **[INTEGRATIONS.md](./INTEGRATIONS.md)** - Payment, fiscal, printer, cash drawer and barcode provider contracts
- **[REQUIREMENTS.md](./REQUIREMENTS.md)** - Technical specification, entities, business rules, API endpoints
- **[TESTING.md](./TESTING.md)** - Test commands, coverage areas, CI expectations

### For Operations & DevOps

- **[PRODUCTION.md](./PRODUCTION.md)** - Production checklist, release gate, runtime requirements
- **[SECURITY.md](./SECURITY.md)** - API auth, roles, idempotency, customer registration links
- **[../doc/openapi.yaml](../doc/openapi.yaml)** - OpenAPI 3.0 API specification

### For Project Managers

- **[REQUIREMENTS.md](./REQUIREMENTS.md)** - Scope, features, roadmap
- **[MIGRATION.md](./MIGRATION.md)** - Completed HeroUI migration status
- **[EXECUTION_PLAN_P0_P2.md](./EXECUTION_PLAN_P0_P2.md)** - Plan operativo P1/P2 con épicas, KPIs y DoD

---

## 📖 Documentation by Topic

### Getting Started

1. Read [README.md](../README.md) for overview
2. Run `composer setup && npm install`
3. Review [REQUIREMENTS.md](./REQUIREMENTS.md) for business context
4. Check [USER_GUIDE.md](./USER_GUIDE.md) to understand workflows

### Development

- **Component Development**: See [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- **API Development**: See [ARCHITECTURE.md](./ARCHITECTURE.md#api-architecture) + [../doc/openapi.yaml](../doc/openapi.yaml)
- **Database**: See [REQUIREMENTS.md](./REQUIREMENTS.md#3-core-entities)
- **Authentication**: See [SECURITY.md](./SECURITY.md#api-authentication)
- **Testing**: See [TESTING.md](./TESTING.md)

### Deployment

- **Pre-Deploy**: See [PRODUCTION.md](./PRODUCTION.md)
- **CI/CD Pipeline**: See [ARCHITECTURE.md](./ARCHITECTURE.md#builddeployment)
- **Production Checklist**: See [ARCHITECTURE.md](./ARCHITECTURE.md#production-checklist)

### Operations

- **Queue Management**: See [ARCHITECTURE.md](./ARCHITECTURE.md#queue-worker)
- **Monitoring**: See [ARCHITECTURE.md](./ARCHITECTURE.md#monitoring--troubleshooting)
- **Troubleshooting**: See [USER_GUIDE.md](./USER_GUIDE.md#troubleshooting)

---

## 🎯 Core Concepts

### Atomic Design System (ADS)

All UI uses a custom component library following atomic design principles:

- **Atoms**: Button, Card, Text, Input, Badge, Toggle
- **Molecules**: FormField, Modal, Navbar, StatCard, ThemeToggle
- **Organisms**: DataTable, AppLayout

→ Details: [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)

### Dark/Light Theme

- CSS custom properties for all colors
- Instant theme toggle via React Context
- All components automatically adapt

→ Details: [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md#theme-system)

### Domain-Driven Backend

Business logic organized by domain:

- Catalog (products, warehouses, types)
- Inventory (stock, reorder rules)
- POS (carts, checkout)
- Sales (transactions, fulfillment)

→ Details: [ARCHITECTURE.md](./ARCHITECTURE.md#3-domain-driven-design-backend)

### API Versioning

All endpoints under `/api/v1/` with standard response format:

```json
{ "success": true, "data": {...} }
```

→ Details: [../doc/openapi.yaml](../doc/openapi.yaml)

---

## 📊 Technology Stack

| Layer    | Technology                               |
| -------- | ---------------------------------------- |
| Backend  | Laravel 12, PHP 8.3, MySQL 8.x           |
| Frontend | React 19, Inertia.js, TailwindCSS 4, ADS |
| Build    | Vite, npm, Composer                      |
| Auth     | equidna/swift-auth                       |
| Queue    | Redis / Database driver                  |

→ Details: [ARCHITECTURE.md](./ARCHITECTURE.md#technology-stack)

---

## 🚀 Common Tasks

| Task                   | Documentation                                                |
| ---------------------- | ------------------------------------------------------------ |
| Set up dev environment | [README.md](../README.md#quick-start)                        |
| Create a new page      | [ARCHITECTURE.md](./ARCHITECTURE.md#extending-the-system)    |
| Add a component        | [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md#extending-the-system)  |
| Create an API endpoint | [ARCHITECTURE.md](./ARCHITECTURE.md#api-architecture)        |
| Deploy to production   | [ARCHITECTURE.md](./ARCHITECTURE.md#deployment--maintenance) |
| Debug an issue         | [USER_GUIDE.md](./USER_GUIDE.md#troubleshooting)             |
| Migrate database       | [REQUIREMENTS.md](./REQUIREMENTS.md#3-core-entities)         |

---

## 📝 Document Purposes

### ARCHITECTURE.md

- System design and tech stack
- Directory structure
- Architectural decisions (ADS, theme system, DDD)
- API and frontend architecture
- Build, deployment, and monitoring
- **Audience**: Developers, DevOps

### DESIGN_SYSTEM.md

- Component library (atoms, molecules, organisms)
- Design tokens and CSS variables
- Theme system (light/dark modes)
- Component usage examples
- Extending the system
- **Audience**: Frontend developers, UI designers

### REQUIREMENTS.md

- Functional and technical specification
- Core entities and database schema
- Business rules (inventory, discounts, checkout, etc.)
- API endpoints (RESTful)
- UI workflows
- Security and performance considerations
- Acceptance criteria and roadmap
- **Audience**: Product managers, developers, QA

### USER_GUIDE.md

- Step-by-step application usage
- Login, POS, catalog, reports workflows
- Troubleshooting common issues
- Browser requirements
- Demo credentials
- **Audience**: End users, support staff

### MIGRATION.md

- HeroUI phase-out history
- ADS migration status
- Component replacements
- Bundle size and performance impact
- Lessons learned
- **Audience**: Developers, project leads

### EXECUTION_PLAN_P0_P2.md

- Ejecución detallada por fases P1/P2
- Épicas, historias, entregables técnicos y DoD
- KPIs operativos/comerciales y matriz de priorización
- Plan sugerido por sprints
- **Audience**: Product, Engineering, Operations

---

## 🔗 Related Resources

- **API Specification**: [doc/openapi.yaml](../doc/openapi.yaml) (OpenAPI 3.0)
- **Legacy Docs**: [doc/](../doc/) (archived; see `/docs` instead)
- **Project Root**: [README.md](../README.md)

---

## 📞 Support

- **Questions about usage?** → [USER_GUIDE.md](./USER_GUIDE.md#getting-help)
- **Questions about development?** → [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Questions about design?** → [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- **Bugs or features?** → Open an issue in your tracker

---

## ✅ Documentation Status

| Document                                             | Status      | Last Updated |
| ---------------------------------------------------- | ----------- | ------------ |
| [ARCHITECTURE.md](./ARCHITECTURE.md)                 | ✅ Current  | Dec 2025     |
| [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)               | ✅ Current  | Dec 2025     |
| [INTEGRATIONS.md](./INTEGRATIONS.md)                 | ✅ Current  | Apr 2026     |
| [REQUIREMENTS.md](./REQUIREMENTS.md)                 | ✅ Current  | Dec 2025     |
| [USER_GUIDE.md](./USER_GUIDE.md)                     | ✅ Current  | Dec 2025     |
| [MIGRATION.md](./MIGRATION.md)                       | ✅ Complete | Dec 2025     |
| [EXECUTION_PLAN_P0_P2.md](./EXECUTION_PLAN_P0_P2.md) | ✅ Current  | Feb 2026     |
| [README.md](../README.md)                            | ✅ Current  | Dec 2025     |

**Consolidation**: ✅ Complete — no duplicates, all outdated references removed
