# 📚 Documentation Guide

**POS Faro documentation is fully consolidated.** Start here to find what you need.

## ⚡ Quick Links

| Need                      | Link                                             |
| ------------------------- | ------------------------------------------------ |
| **Project overview**      | [README.md](./README.md)                         |
| **How to use POS**        | [docs/USER_GUIDE.md](./docs/USER_GUIDE.md)       |
| **How to build & deploy** | [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md)   |
| **Component library**     | [docs/DESIGN_SYSTEM.md](./docs/DESIGN_SYSTEM.md) |
| **Technical spec**        | [docs/REQUIREMENTS.md](./docs/REQUIREMENTS.md)   |
| **Full navigation**       | [docs/INDEX.md](./docs/INDEX.md)                 |

## 📂 Documentation Structure

```
POS Faro/
├── README.md                      ← Start here!
├── docs/                          ← All current documentation
│   ├── INDEX.md                   ← Full navigation
│   ├── ARCHITECTURE.md            ← System design & deployment
│   ├── DESIGN_SYSTEM.md           ← Component library & theme system
│   ├── REQUIREMENTS.md            ← Technical spec & business rules
│   ├── USER_GUIDE.md              ← How to use POS
│   └── MIGRATION.md               ← HeroUI → ADS migration (complete)
├── doc/                           ← Legacy (still contains openapi.yaml)
│   ├── README.md                  ← See docs/ instead
│   ├── openapi.yaml               ← API specification (still used)
│   ├── requirements.md            ← Archived (see docs/REQUIREMENTS.md)
│   └── user-manual.md             ← Archived (see docs/USER_GUIDE.md)
└── .archived-docs/                ← Old docs (historical reference)
    ├── HEROUI_PHASE_OUT.md
    ├── ADS_COMPLETION_REPORT.md
    └── DESIGN_SYSTEM.md (old)
```

## 🎯 By Role

**End Users / Support**
→ [docs/USER_GUIDE.md](./docs/USER_GUIDE.md) - Learn how to use POS Faro

**Developers**
→ [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) + [docs/DESIGN_SYSTEM.md](./docs/DESIGN_SYSTEM.md)

**Product / QA**
→ [docs/REQUIREMENTS.md](./docs/REQUIREMENTS.md) - Features, scope, roadmap

**DevOps / Operations**
→ [docs/ARCHITECTURE.md#deployment--maintenance](./docs/ARCHITECTURE.md#deployment--maintenance)

**UI / Frontend**
→ [docs/DESIGN_SYSTEM.md](./docs/DESIGN_SYSTEM.md) - Component library

## ✅ Status

-   ✅ All documentation consolidated into `/docs/`
-   ✅ Zero duplication
-   ✅ All outdated references removed
-   ✅ Old docs archived in `.archived-docs/`
-   ✅ README.md updated with current info
-   ✅ Build passes, no errors

## 🚀 Getting Started

```bash
# 1. Read the overview
cat README.md

# 2. See full doc navigation
cat docs/INDEX.md

# 3. Pick your role and read relevant docs
# (links above)
```

---

**Questions?** Check [docs/INDEX.md](./docs/INDEX.md) for comprehensive navigation.
