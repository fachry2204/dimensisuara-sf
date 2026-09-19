# Brainstorm: React SPA Migration

**Date:** 2026-05-09
**Status:** Complete — ready for `/plan`

---

## Decision Summary

| Decision | Choice |
|----------|--------|
| Framework | Vite + React + TypeScript |
| Styling | Tailwind CSS v4 |
| Component Library | Shadcn/UI (copy-paste, you own the code) |
| Data Table | TanStack Table (headless logic, custom UI) |
| Data Fetching | TanStack Query |
| Routing | React Router v7 |
| Forms | React Hook Form + Zod validation |
| Auth | Laravel session-based (web routes, cookie returned to frontend) |
| i18n | react-i18next (English + Bahasa Indonesia) |
| Whitelabeling | CSS custom properties + ThemeProvider (colors, logo, brand name, custom domain) |
| Scope | Both Customer + Admin panels (109 Blade templates → ~85 React pages) |
| API Design | Restructured RESTful (resource-based, not 1:1 Blade mirror) |

## Enhancements Included

1. **Optimistic UI updates** — instant feedback on mutations
2. **Skeleton loaders** — professional loading states
3. **Toast notifications** — Sonner (stacking, auto-dismiss)
4. **i18n** — English + Bahasa Indonesia (mandatory)
5. **Dark/Light/System theme** — Tailwind v4 CSS-based

---

## Context & Motivation

- Separate frontend/backend concerns
- Ditch Bootstrap for fluid UI/UX modification
- Enable future UI/UX redesigns without backend coupling
- Whitelabeling for multi-tenant (color scheme, logo, brand name, custom domain)

---

## Current Codebase Analysis

- **109 Blade templates**, ~1.2 MB total
- **No existing API layer** — all web routes returning views
- **UI**: Bootstrap 5 (dark theme) + custom SCSS + jQuery
- **Plugins**: DataTable (23 files), Select2 (9), BS-Stepper (5), ApexCharts (2), Quill (4), MetisMenu, PerfectScrollbar
- **Heavy inline JS** — some files 100KB+ (releases/create.blade.php = 105KB)
- **2 roles**: Customer panel + Admin panel

---

## Architecture

### Hosting

```
app.soundfresh.id/          → React SPA (Vite build, served via .htaccess)
app.soundfresh.id/api/      → Laravel API (session auth, JSON responses)
```

### Project Structure

```
src/
├── app/
│   ├── routes/
│   │   ├── auth/
│   │   ├── customer/
│   │   ├── admin/
│   │   └── guest/
│   └── App.tsx
├── components/
│   ├── ui/                          # Shadcn components
│   ├── layout/
│   │   ├── AppLayout.tsx
│   │   ├── Sidebar.tsx
│   │   ├── Topbar.tsx
│   │   └── GuestLayout.tsx
│   ├── data-table/
│   │   ├── DataTable.tsx
│   │   ├── DataTablePagination.tsx
│   │   ├── DataTableFilter.tsx
│   │   └── DataTableColumnHeader.tsx
│   ├── badges/
│   ├── charts/
│   └── skeletons/
├── features/
│   ├── auth/
│   ├── customer/
│   │   ├── dashboard/
│   │   ├── releases/
│   │   ├── publishing/
│   │   ├── analytics/
│   │   ├── earnings/
│   │   └── settlements/
│   └── admin/
│       ├── aggregator/
│       ├── publishing/
│       ├── users/
│       ├── broadcast/
│       ├── announcements/
│       ├── automation/
│       ├── logging/
│       └── roles/
├── lib/
│   ├── api.ts
│   ├── query-client.ts
│   └── utils.ts
├── providers/
│   ├── ThemeProvider.tsx
│   ├── AuthProvider.tsx
│   └── QueryProvider.tsx
├── i18n/
│   ├── config.ts
│   ├── en/
│   └── id/
├── theme/
│   ├── themes.ts
│   └── index.css
└── types/
```

### Whitelabeling

```css
@theme {
  --color-primary: var(--brand-primary, #ff6a00);
  --color-primary-foreground: var(--brand-primary-fg, #ffffff);
  --color-secondary: var(--brand-secondary, #1a1a2e);
  --color-accent: var(--brand-accent, #ffd200);
  --color-sidebar: var(--brand-sidebar, #0f0f23);
}
```

- Tenant config from `GET /api/tenant/config` → `{ brandName, logoUrl, colors }`
- ThemeProvider sets CSS variables on `:root`

### Auth Flow (Session + Cookie)

```
1. React loads → GET /api/auth/csrf-cookie (sets XSRF-TOKEN cookie)
2. GET /api/auth/user (with session cookie)
3. If 401 → redirect to /login
4. POST /api/auth/login (CSRF token + credentials)
5. Laravel creates session → sets session cookie → returns user + permissions JSON
6. React stores user in AuthContext
7. All subsequent API calls include session cookie (same-domain, automatic)
8. Permissions checked client-side via can() helper mirroring Laravel gates
```

**Important:** Laravel must set `SESSION_DOMAIN=.soundfresh.id` and configure CORS + cookie settings.

### State Management

| Concern | Tool |
|---------|------|
| Server state | TanStack Query |
| Auth / permissions | React Context |
| UI state | localStorage + React state |
| Form state | React Hook Form + Zod |
| Language | react-i18next (persisted to localStorage) |

### API Contract Design (Restructured RESTful)

Resource-based endpoints. Example — Dashboard:
```
GET /api/stats/overview          → { totalRevenue, totalSongs, totalReleases }
GET /api/stats/top-streamed      → [{ track, totalViews }]
GET /api/stats/stream-graph      → [{ period, views }]
GET /api/stats/revenue-graph     → [{ period, amount }]
GET /api/announcements           → [{ id, title, content, startDate, endDate }]
```

All list endpoints support: `?page=&per_page=&sort=&direction=&search=&filter[status]=`

---

## Effort Estimation

| Area | Scope | Days |
|------|-------|------|
| Scaffolding + Tailwind v4 + Vite | — | 2-3 |
| Whitelabeling system | — | 2-3 |
| i18n setup + translations (EN + ID) | — | 3-4 |
| Shared components (Layout, DataTable, Forms, Modals, Badges, Skeletons, Toasts) | ~25 components | 8-10 |
| Auth pages | 6 pages | 4-5 |
| Customer panel | ~18 pages | 12-16 |
| Admin panel | ~35 pages | 18-24 |
| Guest/Public pages | ~6 pages | 2-3 |
| API contract definition | ~80 endpoints | 4-5 |
| Theme system (Dark/Light/System) | — | 1-2 |
| Optimistic UI + Skeletons + Toasts | — | 2-3 |
| Integration + polish | — | 4-5 |
| **Total** | **~85 pages, ~25 components, ~80 endpoints** | **~50-70 days** |

~2.5-3.5 months solo, or ~1.5-2 months with 2 devs.

---

## Key Risks

1. **releases/create.blade.php (105KB)** — Most complex page with multi-step form, file chunk upload
2. **register.blade.php (61KB)** — Complex multi-step registration
3. **No existing API** — Must be built from scratch (user's responsibility)
4. **jQuery → React** — Imperative → declarative, not a direct translation
5. **Session auth + SPA** — Requires proper CORS, cookie config, CSRF handling
