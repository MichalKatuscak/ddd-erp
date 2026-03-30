# Frontend ERP — Design Spec

**Datum:** 2026-03-30
**Status:** Schváleno

---

## 1. Přehled

React 19 + TypeScript SPA pro interní ERP systém. Komunikuje s PHP/Symfony backendem přes REST API (JWT Bearer auth). Obsahuje vlastní design system s Storybookem, CRM modul (zákazníci) a Identity admin modul (uživatelé, role).

---

## 2. Tech stack

| Vrstva | Technologie |
|---|---|
| Framework | React 19 + TypeScript |
| Bundler | Vite 8 |
| Router | TanStack Router |
| Server state | TanStack Query |
| Client state | Zustand (auth store) |
| Styling | CSS Modules + CSS custom properties |
| Dokumentace komponent | Storybook |

---

## 3. Fyzická struktura

```
frontend/
├── .storybook/
│   ├── main.ts
│   └── preview.ts
├── src/
│   ├── design-system/              # izolovaný — žádné importy z app/
│   │   ├── tokens/
│   │   │   └── tokens.css          # CSS custom properties
│   │   ├── components/
│   │   │   ├── Button/
│   │   │   │   ├── Button.tsx
│   │   │   │   ├── Button.module.css
│   │   │   │   └── Button.stories.tsx
│   │   │   ├── Input/
│   │   │   │   ├── Input.tsx
│   │   │   │   ├── Input.module.css
│   │   │   │   └── Input.stories.tsx
│   │   │   ├── FormField/
│   │   │   │   ├── FormField.tsx
│   │   │   │   ├── FormField.module.css
│   │   │   │   └── FormField.stories.tsx
│   │   │   ├── Table/
│   │   │   │   ├── Table.tsx
│   │   │   │   ├── Table.module.css
│   │   │   │   └── Table.stories.tsx
│   │   │   ├── Badge/
│   │   │   │   ├── Badge.tsx
│   │   │   │   ├── Badge.module.css
│   │   │   │   └── Badge.stories.tsx
│   │   │   ├── Modal/
│   │   │   │   ├── Modal.tsx
│   │   │   │   ├── Modal.module.css
│   │   │   │   └── Modal.stories.tsx
│   │   │   └── PageLayout/
│   │   │       ├── PageLayout.tsx
│   │   │       ├── PageLayout.module.css
│   │   │       └── PageLayout.stories.tsx
│   │   └── index.ts                # public API design systemu
│   ├── app/
│   │   ├── router.tsx              # TanStack Router — definice všech routes
│   │   ├── auth/
│   │   │   ├── authStore.ts        # Zustand store (token, user, permissions)
│   │   │   ├── useAuth.ts          # hook pro přístup k auth stavu
│   │   │   └── authGuard.ts        # beforeLoad helper pro route guards
│   │   ├── api/
│   │   │   ├── client.ts           # fetch wrapper s JWT hlavičkou + 401 handler
│   │   │   ├── identity.ts         # typed funkce pro Identity API
│   │   │   └── crm.ts              # typed funkce pro CRM API
│   │   └── modules/
│   │       ├── auth/
│   │       │   └── LoginPage.tsx
│   │       ├── crm/
│   │       │   ├── CustomersPage.tsx
│   │       │   └── CustomerDetailPage.tsx
│   │       └── identity/
│   │           ├── UsersPage.tsx
│   │           ├── UserDetailPage.tsx
│   │           ├── RolesPage.tsx
│   │           └── RoleDetailPage.tsx
│   └── main.tsx
└── package.json
```

**Pravidlo izolace:** `design-system/` importuje pouze React a CSS — nikdy nic z `app/`. `app/` importuje komponenty výhradně z `design-system/index.ts`.

---

## 4. Design system

### 4.1 Tokeny (`tokens.css`)

CSS custom properties definované na `:root`:

**Barvy:**
```css
--color-primary-50 … --color-primary-900   /* hlavní brand barva */
--color-neutral-50 … --color-neutral-900   /* šedé odstíny */
--color-danger-500, --color-danger-600      /* chyby, destruktivní akce */
--color-success-500                         /* úspěch, aktivní stavy */
--color-warning-500                         /* varování */
```

**Spacing (4px grid):**
```css
--space-1: 4px;   --space-2: 8px;   --space-3: 12px;  --space-4: 16px;
--space-5: 20px;  --space-6: 24px;  --space-8: 32px;  --space-10: 40px;
--space-12: 48px; --space-16: 64px;
```

**Typografie:**
```css
--font-size-xs: 11px;  --font-size-sm: 13px;  --font-size-md: 15px;
--font-size-lg: 17px;  --font-size-xl: 20px;  --font-size-2xl: 24px;
--font-weight-normal: 400;  --font-weight-medium: 500;  --font-weight-semibold: 600;
--line-height-tight: 1.25;  --line-height-normal: 1.5;
```

**Ostatní:**
```css
--radius-sm: 4px;  --radius-md: 8px;  --radius-lg: 12px;
--shadow-sm: 0 1px 3px rgba(0,0,0,.08);
--shadow-md: 0 4px 12px rgba(0,0,0,.10);
```

### 4.2 Komponenty

| Komponenta | Varianty / props |
|---|---|
| `Button` | variant: primary/secondary/ghost/danger; size: sm/md/lg; loading; disabled |
| `Input` | type, placeholder, error, disabled |
| `FormField` | label, error (string), hint, children (Input) |
| `Table` | columns (key, header, render?), data, loading skeleton |
| `Badge` | variant: neutral/success/danger/primary; label |
| `Modal` | open, onClose, title, children; focus trap, ESC close |
| `PageLayout` | sidebar slot + main slot |

### 4.3 Storybook

- Storybook 8, běží na `localhost:6006`
- Každá komponenta má stories pro všechny varianty a stavy (loading, error, disabled, hover)
- `preview.ts` importuje `tokens.css` globálně
- Spuštění: `npm run storybook`

---

## 5. Auth

### 5.1 Úložiště tokenů

- **Access token:** `sessionStorage` — nevydrží zavření tabu, bezpečnější než localStorage
- **Refresh token:** `sessionStorage` — backend prozatím vrací v JSON body (ne httpOnly cookie)

### 5.2 Auth store (Zustand)

```ts
interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  user: { id: string; email: string; firstName: string; lastName: string } | null
  permissions: string[]
  login(email: string, password: string): Promise<void>
  logout(): Promise<void>
  refresh(): Promise<void>
  hasPermission(permission: string): boolean
}
```

### 5.3 API client

`client.ts` — fetch wrapper:
1. Přidá `Authorization: Bearer <accessToken>` hlavičku
2. Při 401 response zavolá `refresh()` a zopakuje request jednou
3. Pokud refresh selže → `logout()` + redirect na `/login`

### 5.4 useAuth hook

Vrací auth stav ze Zustand store. Používá se v komponentách i route guardech.

---

## 6. Routing (TanStack Router)

```
/login                          PUBLIC — LoginPage
/                               → redirect na /crm/customers
/crm/customers                  GUARD: crm.contacts.view_customers
/crm/customers/$customerId      GUARD: crm.contacts.view_customers
/identity/users                 GUARD: identity.users.manage
/identity/users/$userId         GUARD: identity.users.manage
/identity/roles                 GUARD: identity.roles.manage
/identity/roles/$roleId         GUARD: identity.roles.manage
```

**Route guard** (`authGuard.ts`): `beforeLoad` funkce — pokud uživatel nemá permission, redirect na `/` (nebo `/login` pokud není přihlášen).

---

## 7. Stránky

### 7.1 Login (`/login`)

- Email + password formulář
- Submit → `authStore.login()` → POST `/api/identity/commands/login`
- Chyba 401 → "Nesprávné přihlašovací údaje" pod formulářem
- Po úspěchu → redirect na `/`

### 7.2 CRM Customers (`/crm/customers`)

- Tabulka: jméno (firstName + lastName), email, telefon, město
- TanStack Query: `useQuery` na GET `/api/crm/contacts/customers`
- Tlačítko "Přidat zákazníka" → Modal s formulářem → POST register-customer → invalidate query
- Klik na řádek → `/crm/customers/$customerId`

### 7.3 Customer Detail (`/crm/customers/$customerId`)

- Formulář s daty zákazníka (prefilled)
- Tlačítko "Uložit" → PUT update-customer → invalidate
- Breadcrumb: Zákazníci → {jméno}

### 7.4 Identity Users (`/identity/users`)

- Tabulka: email, jméno, role (Badge pro každou roli), aktivní (Badge)
- TanStack Query: GET `/api/identity/users`
- Tlačítko "Přidat uživatele" → Modal → POST register-user
- Klik → `/identity/users/$userId`

### 7.5 User Detail (`/identity/users/$userId`)

- Edit formulář (email, jméno)
- Sekce "Role" — multiselect z GET `/api/identity/roles` → POST assign-roles
- Tlačítko "Deaktivovat" (visible pokud active) → POST deactivate-user → invalidate

### 7.6 Identity Roles (`/identity/roles`)

- Tabulka: název role, počet permissions
- Tlačítko "Vytvořit roli" → Modal → POST create-role
- Klik → `/identity/roles/$roleId`

### 7.7 Role Detail (`/identity/roles/$roleId`)

- Název role (readonly)
- Checklist permissions rozdělených do skupin (CRM, Identity)
- Dostupné permissions: `crm.contacts.view_customers`, `crm.contacts.manage_customers`, `crm.contacts.export_customers`, `identity.users.manage`, `identity.roles.manage`, `identity.users.view`
- Tlačítko "Uložit" → PUT update-role-permissions → invalidate

### 7.8 Navigace (sidebar)

- Logo / název "ERP"
- Sekce **CRM**: odkaz Zákazníci (viditelný pro `crm.contacts.view_customers`)
- Sekce **Administrace**: Uživatelé, Role (viditelné pro `identity.users.manage` resp. `identity.roles.manage`)
- Dole: avatar + email přihlášeného uživatele, tlačítko Odhlásit

---

## 8. Implementační sub-projekty

Implementace je rozdělena do 3 samostatných sub-projektů (každý má vlastní spec → plan → worktree):

1. **Foundation** — Storybook setup, design tokeny, základní komponenty (Button, Input, FormField, Badge, Modal, Table, PageLayout), auth store, API client, router shell, Login stránka
2. **CRM modul** — CustomersPage, CustomerDetailPage napojené na backend
3. **Identity admin modul** — UsersPage, UserDetailPage, RolesPage, RoleDetailPage

---

## 9. Závislosti (nové)

```json
{
  "dependencies": {
    "@tanstack/react-router": "^1.x",
    "@tanstack/react-query": "^5.x",
    "zustand": "^5.x"
  },
  "devDependencies": {
    "@storybook/react-vite": "^8.x",
    "@storybook/addon-essentials": "^8.x",
    "storybook": "^8.x"
  }
}
```
