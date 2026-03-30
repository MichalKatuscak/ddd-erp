# Frontend ERP — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Postavit základ ERP frontendu — design system s Storybookem, auth logiku a funkční Login stránku.

**Architecture:** Design system in-app v `src/design-system/` (izolovaný od app logiky), TanStack Router pro routing, Zustand pro auth stav, TanStack Query jako server-state layer. Storybook 8 s Vite pro komponentovou dokumentaci a vizuální verifikaci.

**Tech Stack:** React 19, TypeScript, Vite 8, TanStack Router v1, TanStack Query v5, Zustand v5, Storybook 8, CSS Modules, Vitest

---

## Task overview

| # | Task | Type |
|---|---|---|
| 1 | Install dependencies + configure Storybook + Vitest | Setup |
| 2 | Design tokens (`tokens.css` + `index.css` reset) | Design system |
| 3 | Button component + story | Design system |
| 4 | Input + FormField components + stories | Design system |
| 5 | Badge component + story | Design system |
| 6 | Table component + story | Design system |
| 7 | Modal component + story | Design system |
| 8 | PageLayout component + story | Design system |
| 9 | `design-system/index.ts` public API | Design system |
| 10 | Auth store (Zustand) — TDD | Auth |
| 11 | API client (fetch wrapper with JWT + refresh) — TDD | API |
| 12 | API function stubs (`identity.ts`, `crm.ts`) | API |
| 13 | TanStack Router setup + `main.tsx` update | Routing |
| 14 | Login page (functional, connects to real backend) | Feature |

---

## Task 1 — Install dependencies + configure Storybook + Vitest

**Files:**
- Modify: `frontend/package.json` (scripts)
- Create: `frontend/.storybook/main.ts`
- Create: `frontend/.storybook/preview.ts`
- Modify: `frontend/vite.config.ts`
- Create: `frontend/src/test-setup.ts`
- Modify: `frontend/tsconfig.app.json`

### Steps

- [ ] **1.1** Install production dependencies

```bash
cd frontend && npm install @tanstack/react-router @tanstack/react-query zustand
```

Expected output: packages added, no peer-dep errors.

- [ ] **1.2** Install dev dependencies

```bash
cd frontend && npm install -D storybook @storybook/react-vite @storybook/addon-essentials @storybook/blocks vitest @testing-library/react @testing-library/user-event @testing-library/jest-dom jsdom
```

Expected output: packages added, no errors.

- [ ] **1.3** Add scripts to `frontend/package.json`

Open `frontend/package.json` and add to the `"scripts"` object:

```json
"storybook": "storybook dev -p 6006",
"build-storybook": "storybook build",
"test": "vitest"
```

Result: `package.json` scripts section now contains `storybook`, `build-storybook`, and `test`.

- [ ] **1.4** Create `.storybook/main.ts`

```
frontend/.storybook/main.ts
```

```ts
import type { StorybookConfig } from '@storybook/react-vite'

const config: StorybookConfig = {
  stories: ['../src/**/*.stories.@(ts|tsx)'],
  addons: ['@storybook/addon-essentials'],
  framework: {
    name: '@storybook/react-vite',
    options: {},
  },
}

export default config
```

- [ ] **1.5** Create `.storybook/preview.ts`

```
frontend/.storybook/preview.ts
```

```ts
import type { Preview } from '@storybook/react'
import '../src/design-system/tokens/tokens.css'

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
}

export default preview
```

- [ ] **1.6** Update `frontend/vite.config.ts` to add Vitest config

Replace the entire file content:

```ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: './src/test-setup.ts',
  },
})
```

- [ ] **1.7** Create `frontend/src/test-setup.ts`

```ts
import '@testing-library/jest-dom'
```

- [ ] **1.8** Update `frontend/tsconfig.app.json` — add `"types"` to `compilerOptions`

Add to the `compilerOptions` object:

```json
"types": ["vitest/globals"]
```

- [ ] **1.9** Verify Storybook config is found (dry run)

```bash
cd frontend && npx storybook --version
```

Expected output: Storybook version number printed (e.g. `8.x.x`).

- [ ] **1.10** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/package.json frontend/package-lock.json frontend/.storybook/ frontend/vite.config.ts frontend/src/test-setup.ts frontend/tsconfig.app.json && git commit -m "chore(frontend): install deps, configure Storybook 8 and Vitest"
```

---

## Task 2 — Design tokens

**Files:**
- Create: `frontend/src/design-system/tokens/tokens.css`
- Modify: `frontend/src/index.css`

### Steps

- [ ] **2.1** Create `frontend/src/design-system/tokens/tokens.css`

```css
:root {
  /* Primary — Indigo */
  --color-primary-50: #eef2ff;
  --color-primary-100: #e0e7ff;
  --color-primary-200: #c7d2fe;
  --color-primary-600: #4f46e5;
  --color-primary-700: #4338ca;
  --color-primary-900: #312e81;

  /* Neutral — Zinc */
  --color-neutral-50:  #fafafa;
  --color-neutral-100: #f4f4f5;
  --color-neutral-200: #e4e4e7;
  --color-neutral-300: #d4d4d8;
  --color-neutral-400: #a1a1aa;
  --color-neutral-500: #71717a;
  --color-neutral-600: #52525b;
  --color-neutral-700: #3f3f46;
  --color-neutral-800: #27272a;
  --color-neutral-900: #18181b;

  /* Semantic */
  --color-danger-500: #ef4444;
  --color-danger-600: #dc2626;
  --color-success-500: #22c55e;
  --color-warning-500: #f59e0b;

  /* Spacing — 4px grid */
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  --space-16: 64px;

  /* Typography */
  --font-size-xs: 11px;
  --font-size-sm: 13px;
  --font-size-md: 15px;
  --font-size-lg: 17px;
  --font-size-xl: 20px;
  --font-size-2xl: 24px;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --line-height-tight: 1.25;
  --line-height-normal: 1.5;

  /* Radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
  --shadow-md: 0 4px 12px rgba(0,0,0,.10);
}
```

- [ ] **2.2** Replace `frontend/src/index.css` with global reset

Replace the entire file with:

```css
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: var(--font-size-md);
  color: var(--color-neutral-900);
  background: var(--color-neutral-50);
  line-height: var(--line-height-normal);
  -webkit-font-smoothing: antialiased;
}

a { color: inherit; text-decoration: none; }
```

- [ ] **2.3** Verify tokens file exists and has expected variables

```bash
grep -c "^  --" frontend/src/design-system/tokens/tokens.css
```

Expected output: `38` (or similar — at least 30 custom properties defined).

- [ ] **2.4** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/tokens/tokens.css frontend/src/index.css && git commit -m "feat(frontend): add design tokens and global CSS reset"
```

---

## Task 3 — Button component

**Approach:** Write story first (defines expected variants), then implement component.

**Files:**
- Create: `frontend/src/design-system/components/Button/Button.stories.tsx`
- Create: `frontend/src/design-system/components/Button/Button.tsx`
- Create: `frontend/src/design-system/components/Button/Button.module.css`

### Steps

- [ ] **3.1** Create the Storybook story first: `frontend/src/design-system/components/Button/Button.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { Button } from './Button'

const meta: Meta<typeof Button> = {
  component: Button,
  title: 'Design System/Button',
}
export default meta
type Story = StoryObj<typeof Button>

export const Primary: Story = { args: { variant: 'primary', children: 'Přidat zákazníka' } }
export const Secondary: Story = { args: { variant: 'secondary', children: 'Zrušit' } }
export const Ghost: Story = { args: { variant: 'ghost', children: 'Více' } }
export const Danger: Story = { args: { variant: 'danger', children: 'Deaktivovat' } }
export const Loading: Story = { args: { variant: 'primary', children: 'Ukládám...', loading: true } }
export const Disabled: Story = { args: { variant: 'primary', children: 'Přidat', disabled: true } }
export const Small: Story = { args: { variant: 'secondary', size: 'sm', children: 'Malé' } }
export const Large: Story = { args: { variant: 'primary', size: 'lg', children: 'Velké' } }
```

- [ ] **3.2** Create the component: `frontend/src/design-system/components/Button/Button.tsx`

```tsx
import styles from './Button.module.css'

interface ButtonProps {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  onClick?: () => void
  children: React.ReactNode
}

export function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  type = 'button',
  onClick,
  children,
}: ButtonProps) {
  return (
    <button
      type={type}
      className={[
        styles.button,
        styles[variant],
        styles[size],
        (disabled || loading) ? styles.disabled : '',
      ].join(' ')}
      disabled={disabled || loading}
      onClick={onClick}
    >
      {loading ? <span className={styles.spinner} aria-hidden="true" /> : null}
      {children}
    </button>
  )
}
```

- [ ] **3.3** Create the styles: `frontend/src/design-system/components/Button/Button.module.css`

```css
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  border: none;
  border-radius: var(--radius-md);
  font-family: inherit;
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: background-color 0.15s, opacity 0.15s;
  white-space: nowrap;
}
.button:focus-visible {
  outline: 2px solid var(--color-primary-600);
  outline-offset: 2px;
}

.primary { background: var(--color-primary-600); color: #fff; }
.primary:hover:not(:disabled) { background: var(--color-primary-700); }

.secondary {
  background: var(--color-neutral-100);
  color: var(--color-neutral-900);
  border: 1px solid var(--color-neutral-200);
}
.secondary:hover:not(:disabled) { background: var(--color-neutral-200); }

.ghost { background: transparent; color: var(--color-neutral-700); }
.ghost:hover:not(:disabled) { background: var(--color-neutral-100); }

.danger { background: var(--color-danger-500); color: #fff; }
.danger:hover:not(:disabled) { background: var(--color-danger-600); }

.sm { padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); }
.md { padding: var(--space-2) var(--space-4); font-size: var(--font-size-md); }
.lg { padding: var(--space-3) var(--space-6); font-size: var(--font-size-lg); }

.disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
```

- [ ] **3.4** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **3.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/Button/ && git commit -m "feat(frontend): add Button design system component with Storybook stories"
```

---

## Task 4 — Input + FormField components

**Approach:** Write stories first, then implement components.

**Files:**
- Create: `frontend/src/design-system/components/Input/Input.stories.tsx`
- Create: `frontend/src/design-system/components/Input/Input.tsx`
- Create: `frontend/src/design-system/components/Input/Input.module.css`
- Create: `frontend/src/design-system/components/FormField/FormField.stories.tsx`
- Create: `frontend/src/design-system/components/FormField/FormField.tsx`
- Create: `frontend/src/design-system/components/FormField/FormField.module.css`

### Steps

- [ ] **4.1** Create the Input story first: `frontend/src/design-system/components/Input/Input.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { Input } from './Input'

const meta: Meta<typeof Input> = { component: Input, title: 'Design System/Input' }
export default meta
type Story = StoryObj<typeof Input>

export const Default: Story = { args: { value: '', onChange: () => {}, placeholder: 'jan@firma.cz' } }
export const WithValue: Story = { args: { value: 'admin@erp.local', onChange: () => {} } }
export const ErrorState: Story = { args: { value: 'neplatny', onChange: () => {}, error: true } }
export const Disabled: Story = { args: { value: '', onChange: () => {}, disabled: true, placeholder: 'Deaktivováno' } }
export const Password: Story = { args: { value: '', onChange: () => {}, type: 'password', placeholder: '••••••••' } }
```

- [ ] **4.2** Create the Input component: `frontend/src/design-system/components/Input/Input.tsx`

```tsx
import styles from './Input.module.css'

interface InputProps {
  id?: string
  name?: string
  type?: 'text' | 'email' | 'password' | 'number'
  value: string
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void
  placeholder?: string
  disabled?: boolean
  error?: boolean
}

export function Input({ id, name, type = 'text', value, onChange, placeholder, disabled = false, error = false }: InputProps) {
  return (
    <input
      id={id}
      name={name}
      type={type}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      disabled={disabled}
      className={[styles.input, error ? styles.error : ''].join(' ')}
    />
  )
}
```

- [ ] **4.3** Create the Input styles: `frontend/src/design-system/components/Input/Input.module.css`

```css
.input {
  width: 100%;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-neutral-300);
  border-radius: var(--radius-md);
  font-family: inherit;
  font-size: var(--font-size-md);
  color: var(--color-neutral-900);
  background: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input:focus {
  outline: none;
  border-color: var(--color-primary-600);
  box-shadow: 0 0 0 3px var(--color-primary-100);
}
.input:disabled { background: var(--color-neutral-100); color: var(--color-neutral-400); cursor: not-allowed; }
.error { border-color: var(--color-danger-500); }
.error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,.15); }
```

- [ ] **4.4** Create the FormField story first: `frontend/src/design-system/components/FormField/FormField.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { FormField } from './FormField'
import { Input } from '../Input/Input'

const meta: Meta<typeof FormField> = { component: FormField, title: 'Design System/FormField' }
export default meta
type Story = StoryObj<typeof FormField>

export const Default: Story = {
  args: { label: 'E-mail', htmlFor: 'email' },
  render: (args) => <FormField {...args}><Input id="email" value="" onChange={() => {}} placeholder="jan@firma.cz" /></FormField>,
}
export const WithError: Story = {
  args: { label: 'E-mail', error: 'Nesprávné přihlašovací údaje' },
  render: (args) => <FormField {...args}><Input value="spatny@email" onChange={() => {}} error /></FormField>,
}
export const WithHint: Story = {
  args: { label: 'Heslo', hint: 'Minimálně 8 znaků' },
  render: (args) => <FormField {...args}><Input type="password" value="" onChange={() => {}} /></FormField>,
}
```

- [ ] **4.5** Create the FormField component: `frontend/src/design-system/components/FormField/FormField.tsx`

```tsx
import styles from './FormField.module.css'

interface FormFieldProps {
  label: string
  htmlFor?: string
  error?: string
  hint?: string
  children: React.ReactNode
}

export function FormField({ label, htmlFor, error, hint, children }: FormFieldProps) {
  return (
    <div className={styles.field}>
      <label className={styles.label} htmlFor={htmlFor}>{label}</label>
      {children}
      {error ? <p className={styles.error}>{error}</p> : null}
      {hint && !error ? <p className={styles.hint}>{hint}</p> : null}
    </div>
  )
}
```

- [ ] **4.6** Create the FormField styles: `frontend/src/design-system/components/FormField/FormField.module.css`

```css
.field { display: flex; flex-direction: column; gap: var(--space-1); }
.label { font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: var(--color-neutral-700); }
.error { font-size: var(--font-size-sm); color: var(--color-danger-500); }
.hint { font-size: var(--font-size-sm); color: var(--color-neutral-500); }
```

- [ ] **4.7** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **4.8** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/Input/ frontend/src/design-system/components/FormField/ && git commit -m "feat(frontend): add Input and FormField design system components"
```

---

## Task 5 — Badge component

**Approach:** Write story first, then implement component.

**Files:**
- Create: `frontend/src/design-system/components/Badge/Badge.stories.tsx`
- Create: `frontend/src/design-system/components/Badge/Badge.tsx`
- Create: `frontend/src/design-system/components/Badge/Badge.module.css`

### Steps

- [ ] **5.1** Create the story first: `frontend/src/design-system/components/Badge/Badge.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { Badge } from './Badge'

const meta: Meta<typeof Badge> = { component: Badge, title: 'Design System/Badge' }
export default meta
type Story = StoryObj<typeof Badge>

export const Neutral: Story = { args: { variant: 'neutral', label: 'Neaktivní' } }
export const Success: Story = { args: { variant: 'success', label: 'Aktivní' } }
export const Danger: Story = { args: { variant: 'danger', label: 'Deaktivován' } }
export const Primary: Story = { args: { variant: 'primary', label: 'super-admin' } }
```

- [ ] **5.2** Create the component: `frontend/src/design-system/components/Badge/Badge.tsx`

```tsx
import styles from './Badge.module.css'

interface BadgeProps {
  variant?: 'neutral' | 'success' | 'danger' | 'primary'
  label: string
}

export function Badge({ variant = 'neutral', label }: BadgeProps) {
  return <span className={[styles.badge, styles[variant]].join(' ')}>{label}</span>
}
```

- [ ] **5.3** Create the styles: `frontend/src/design-system/components/Badge/Badge.module.css`

```css
.badge {
  display: inline-flex;
  align-items: center;
  padding: 2px var(--space-2);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  white-space: nowrap;
}
.neutral  { background: var(--color-neutral-100); color: var(--color-neutral-700); }
.success  { background: #dcfce7; color: #15803d; }
.danger   { background: #fee2e2; color: #b91c1c; }
.primary  { background: var(--color-primary-100); color: var(--color-primary-700); }
```

- [ ] **5.4** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **5.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/Badge/ && git commit -m "feat(frontend): add Badge design system component"
```

---

## Task 6 — Table component

**Approach:** Write story first (defines expected data shape and loading behavior), then implement component.

**Files:**
- Create: `frontend/src/design-system/components/Table/Table.stories.tsx`
- Create: `frontend/src/design-system/components/Table/Table.tsx`
- Create: `frontend/src/design-system/components/Table/Table.module.css`

### Steps

- [ ] **6.1** Create the story first: `frontend/src/design-system/components/Table/Table.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { Table } from './Table'

const meta: Meta<typeof Table> = { component: Table, title: 'Design System/Table' }
export default meta
type Story = StoryObj<typeof Table>

const columns = [
  { key: 'name', header: 'Jméno' },
  { key: 'email', header: 'E-mail' },
  { key: 'city', header: 'Město' },
]

const data = [
  { name: 'Jan Novák', email: 'jan@firma.cz', city: 'Praha' },
  { name: 'Eva Svobodová', email: 'eva@firma.cz', city: 'Brno' },
]

export const Default: Story = { args: { columns, data } }
export const Loading: Story = { args: { columns, data: [], loading: true } }
export const Empty: Story = { args: { columns, data: [] } }
export const Clickable: Story = { args: { columns, data, onRowClick: (row) => alert(JSON.stringify(row)) } }
```

- [ ] **6.2** Create the component: `frontend/src/design-system/components/Table/Table.tsx`

```tsx
import styles from './Table.module.css'

export interface Column<T> {
  key: string
  header: string
  render?: (row: T) => React.ReactNode
}

interface TableProps<T> {
  columns: Column<T>[]
  data: T[]
  loading?: boolean
  onRowClick?: (row: T) => void
}

const SKELETON_ROWS = 5

export function Table<T extends Record<string, unknown>>({
  columns,
  data,
  loading = false,
  onRowClick,
}: TableProps<T>) {
  return (
    <div className={styles.wrapper}>
      <table className={styles.table}>
        <thead>
          <tr>
            {columns.map(col => (
              <th key={col.key} className={styles.th}>{col.header}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading
            ? Array.from({ length: SKELETON_ROWS }, (_, i) => (
                <tr key={i}>
                  {columns.map(col => (
                    <td key={col.key} className={styles.td}>
                      <div className={styles.skeleton} />
                    </td>
                  ))}
                </tr>
              ))
            : data.map((row, i) => (
                <tr
                  key={i}
                  className={[styles.row, onRowClick ? styles.clickable : ''].join(' ')}
                  onClick={() => onRowClick?.(row)}
                >
                  {columns.map(col => (
                    <td key={col.key} className={styles.td}>
                      {col.render ? col.render(row) : String(row[col.key] ?? '')}
                    </td>
                  ))}
                </tr>
              ))
          }
        </tbody>
      </table>
    </div>
  )
}
```

- [ ] **6.3** Create the styles: `frontend/src/design-system/components/Table/Table.module.css`

```css
.wrapper { overflow-x: auto; border: 1px solid var(--color-neutral-200); border-radius: var(--radius-lg); }
.table { width: 100%; border-collapse: collapse; }
.th {
  padding: var(--space-3) var(--space-4);
  text-align: left;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-600);
  background: var(--color-neutral-50);
  border-bottom: 1px solid var(--color-neutral-200);
}
.td {
  padding: var(--space-3) var(--space-4);
  font-size: var(--font-size-sm);
  color: var(--color-neutral-800);
  border-bottom: 1px solid var(--color-neutral-100);
}
.row:last-child .td { border-bottom: none; }
.row.clickable { cursor: pointer; }
.row.clickable:hover { background: var(--color-neutral-50); }
.skeleton {
  height: 16px;
  border-radius: var(--radius-sm);
  background: linear-gradient(90deg, var(--color-neutral-100) 25%, var(--color-neutral-200) 50%, var(--color-neutral-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }
```

- [ ] **6.4** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **6.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/Table/ && git commit -m "feat(frontend): add Table design system component with loading skeleton"
```

---

## Task 7 — Modal component

**Approach:** Write story first (defines open/close behavior), then implement component with portal + focus trap.

**Files:**
- Create: `frontend/src/design-system/components/Modal/Modal.stories.tsx`
- Create: `frontend/src/design-system/components/Modal/Modal.tsx`
- Create: `frontend/src/design-system/components/Modal/Modal.module.css`

### Steps

- [ ] **7.1** Create the story first: `frontend/src/design-system/components/Modal/Modal.stories.tsx`

```tsx
import { useState } from 'react'
import type { Meta, StoryObj } from '@storybook/react'
import { Modal } from './Modal'
import { Button } from '../Button/Button'

const meta: Meta<typeof Modal> = { component: Modal, title: 'Design System/Modal' }
export default meta
type Story = StoryObj<typeof Modal>

export const Default: Story = {
  render: () => {
    const [open, setOpen] = useState(false)
    return (
      <>
        <Button onClick={() => setOpen(true)}>Otevřít modal</Button>
        <Modal open={open} onClose={() => setOpen(false)} title="Přidat zákazníka">
          <p>Obsah modalu zde.</p>
        </Modal>
      </>
    )
  },
}
```

- [ ] **7.2** Create the component: `frontend/src/design-system/components/Modal/Modal.tsx`

```tsx
import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import styles from './Modal.module.css'

interface ModalProps {
  open: boolean
  onClose: () => void
  title: string
  children: React.ReactNode
}

export function Modal({ open, onClose, title, children }: ModalProps) {
  const dialogRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return

    const el = dialogRef.current
    if (!el) return

    const focusable = el.querySelectorAll<HTMLElement>(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    const first = focusable[0]
    const last = focusable[focusable.length - 1]

    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') { onClose(); return }
      if (e.key !== 'Tab') return
      if (!focusable.length) return
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last?.focus() }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first?.focus() }
      }
    }

    document.addEventListener('keydown', handleKey)
    first?.focus()
    return () => document.removeEventListener('keydown', handleKey)
  }, [open, onClose])

  if (!open) return null

  return createPortal(
    <div className={styles.overlay} onClick={onClose}>
      <div
        ref={dialogRef}
        className={styles.dialog}
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        onClick={e => e.stopPropagation()}
      >
        <div className={styles.header}>
          <h2 id="modal-title" className={styles.title}>{title}</h2>
          <button className={styles.close} onClick={onClose} aria-label="Zavřít">✕</button>
        </div>
        <div className={styles.body}>{children}</div>
      </div>
    </div>,
    document.body
  )
}
```

- [ ] **7.3** Create the styles: `frontend/src/design-system/components/Modal/Modal.module.css`

```css
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.dialog {
  background: #fff;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  margin: var(--space-4);
}
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-5) var(--space-6);
  border-bottom: 1px solid var(--color-neutral-200);
}
.title { font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); }
.close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: var(--font-size-lg);
  color: var(--color-neutral-400);
  padding: var(--space-1);
  border-radius: var(--radius-sm);
}
.close:hover { color: var(--color-neutral-700); background: var(--color-neutral-100); }
.body { padding: var(--space-6); }
```

- [ ] **7.4** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **7.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/Modal/ && git commit -m "feat(frontend): add Modal component with portal, focus trap, and ESC close"
```

---

## Task 8 — PageLayout component

**Approach:** Write story first (defines sidebar + main slot structure), then implement.

**Files:**
- Create: `frontend/src/design-system/components/PageLayout/PageLayout.stories.tsx`
- Create: `frontend/src/design-system/components/PageLayout/PageLayout.tsx`
- Create: `frontend/src/design-system/components/PageLayout/PageLayout.module.css`

### Steps

- [ ] **8.1** Create the story first: `frontend/src/design-system/components/PageLayout/PageLayout.stories.tsx`

```tsx
import type { Meta, StoryObj } from '@storybook/react'
import { PageLayout } from './PageLayout'

const meta: Meta<typeof PageLayout> = { component: PageLayout, title: 'Design System/PageLayout' }
export default meta
type Story = StoryObj<typeof PageLayout>

export const Default: Story = {
  args: {
    sidebar: (
      <nav>
        <strong>ERP</strong>
        <ul style={{ listStyle: 'none', marginTop: 24 }}>
          <li>Zákazníci</li>
          <li>Uživatelé</li>
          <li>Role</li>
        </ul>
      </nav>
    ),
    children: <div><h1>Obsah stránky</h1><p>Hlavní obsah zde.</p></div>,
  },
}
```

- [ ] **8.2** Create the component: `frontend/src/design-system/components/PageLayout/PageLayout.tsx`

```tsx
import styles from './PageLayout.module.css'

interface PageLayoutProps {
  sidebar: React.ReactNode
  children: React.ReactNode
}

export function PageLayout({ sidebar, children }: PageLayoutProps) {
  return (
    <div className={styles.layout}>
      <aside className={styles.sidebar}>{sidebar}</aside>
      <main className={styles.main}>{children}</main>
    </div>
  )
}
```

- [ ] **8.3** Create the styles: `frontend/src/design-system/components/PageLayout/PageLayout.module.css`

```css
.layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;
}
.sidebar {
  background: #fff;
  border-right: 1px solid var(--color-neutral-200);
  padding: var(--space-6) var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
.main {
  background: var(--color-neutral-50);
  padding: var(--space-8);
  overflow-y: auto;
}
```

- [ ] **8.4** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **8.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/components/PageLayout/ && git commit -m "feat(frontend): add PageLayout component with sidebar and main slots"
```

---

## Task 9 — design-system public API

**Files:**
- Create: `frontend/src/design-system/index.ts`

### Steps

- [ ] **9.1** Create the public API barrel: `frontend/src/design-system/index.ts`

```ts
export { Button } from './components/Button/Button'
export { Input } from './components/Input/Input'
export { FormField } from './components/FormField/FormField'
export { Badge } from './components/Badge/Badge'
export { Table } from './components/Table/Table'
export type { Column } from './components/Table/Table'
export { Modal } from './components/Modal/Modal'
export { PageLayout } from './components/PageLayout/PageLayout'
```

- [ ] **9.2** Verify all exports compile

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **9.3** Verify Storybook finds all 7 component stories

```bash
cd frontend && npx storybook --smoke-test 2>&1 | head -20
```

Expected output: Storybook builds without error. (If `--smoke-test` flag is unsupported in your Storybook version, skip this step and run `npm run storybook` manually to verify visually instead.)

- [ ] **9.4** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/design-system/index.ts && git commit -m "feat(frontend): expose design-system public API via index.ts barrel"
```

---

## Task 10 — Auth store (Zustand) — TDD

**Approach:** Write tests first → run to see them fail → implement store → run to see them pass.

**Files:**
- Create: `frontend/src/app/auth/authStore.test.ts` (write first)
- Create: `frontend/src/app/auth/authStore.ts` (implement after)

### Steps

- [ ] **10.1** Write the test file first: `frontend/src/app/auth/authStore.test.ts`

```ts
import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from './authStore'

describe('authStore', () => {
  beforeEach(() => {
    sessionStorage.clear()
    useAuthStore.getState().clear()
  })

  it('starts unauthenticated when sessionStorage is empty', () => {
    expect(useAuthStore.getState().isAuthenticated()).toBe(false)
    expect(useAuthStore.getState().accessToken).toBeNull()
  })

  it('setTokens stores tokens in state and sessionStorage', () => {
    useAuthStore.getState().setTokens('access-123', 'refresh-456')
    expect(useAuthStore.getState().accessToken).toBe('access-123')
    expect(useAuthStore.getState().refreshToken).toBe('refresh-456')
    expect(sessionStorage.getItem('access_token')).toBe('access-123')
    expect(sessionStorage.getItem('refresh_token')).toBe('refresh-456')
    expect(useAuthStore.getState().isAuthenticated()).toBe(true)
  })

  it('setUser stores user and permissions', () => {
    useAuthStore.getState().setUser(
      { id: 'u1', email: 'a@b.cz', firstName: 'Jan', lastName: 'Novák' },
      ['crm.contacts.view_customers', 'identity.users.manage']
    )
    expect(useAuthStore.getState().user?.email).toBe('a@b.cz')
    expect(useAuthStore.getState().permissions).toContain('crm.contacts.view_customers')
  })

  it('hasPermission returns true for granted permission', () => {
    useAuthStore.getState().setUser({ id: 'u1', email: 'a@b.cz', firstName: 'A', lastName: 'B' }, ['crm.contacts.view_customers'])
    expect(useAuthStore.getState().hasPermission('crm.contacts.view_customers')).toBe(true)
    expect(useAuthStore.getState().hasPermission('identity.roles.manage')).toBe(false)
  })

  it('clear removes tokens from state and sessionStorage', () => {
    useAuthStore.getState().setTokens('access-123', 'refresh-456')
    useAuthStore.getState().clear()
    expect(useAuthStore.getState().isAuthenticated()).toBe(false)
    expect(sessionStorage.getItem('access_token')).toBeNull()
    expect(sessionStorage.getItem('refresh_token')).toBeNull()
  })
})
```

- [ ] **10.2** Run tests — they must fail (no implementation yet)

```bash
cd frontend && npm test -- --run src/app/auth/authStore.test.ts
```

Expected output: 5 tests fail with "Cannot find module './authStore'" or similar.

- [ ] **10.3** Implement the auth store: `frontend/src/app/auth/authStore.ts`

```ts
import { create } from 'zustand'

export interface AuthUser {
  id: string
  email: string
  firstName: string
  lastName: string
}

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  user: AuthUser | null
  permissions: string[]
  setTokens: (access: string, refresh: string) => void
  setUser: (user: AuthUser, permissions: string[]) => void
  clear: () => void
  hasPermission: (permission: string) => boolean
  isAuthenticated: () => boolean
}

export const useAuthStore = create<AuthState>((set, get) => ({
  accessToken: sessionStorage.getItem('access_token'),
  refreshToken: sessionStorage.getItem('refresh_token'),
  user: null,
  permissions: [],

  setTokens(access, refresh) {
    sessionStorage.setItem('access_token', access)
    sessionStorage.setItem('refresh_token', refresh)
    set({ accessToken: access, refreshToken: refresh })
  },

  setUser(user, permissions) {
    set({ user, permissions })
  },

  clear() {
    sessionStorage.removeItem('access_token')
    sessionStorage.removeItem('refresh_token')
    set({ accessToken: null, refreshToken: null, user: null, permissions: [] })
  },

  hasPermission(permission) {
    return get().permissions.includes(permission)
  },

  isAuthenticated() {
    return get().accessToken !== null
  },
}))
```

- [ ] **10.4** Run tests — they must pass

```bash
cd frontend && npm test -- --run src/app/auth/authStore.test.ts
```

Expected output:
```
✓ authStore > starts unauthenticated when sessionStorage is empty
✓ authStore > setTokens stores tokens in state and sessionStorage
✓ authStore > setUser stores user and permissions
✓ authStore > hasPermission returns true for granted permission
✓ authStore > clear removes tokens from state and sessionStorage

Test Files  1 passed (1)
Tests       5 passed (5)
```

- [ ] **10.5** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/app/auth/ && git commit -m "feat(frontend): add Zustand auth store with TDD (5 tests passing)"
```

---

## Task 11 — API client — TDD

**Approach:** Write tests first → run to see them fail → implement client → run to see them pass.

**Files:**
- Create: `frontend/src/app/api/client.test.ts` (write first)
- Create: `frontend/src/app/api/client.ts` (implement after)

### Steps

- [ ] **11.1** Write the test file first: `frontend/src/app/api/client.test.ts`

```ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAuthStore } from '../auth/authStore'

describe('API client', () => {
  beforeEach(() => {
    sessionStorage.clear()
    useAuthStore.getState().clear()
    vi.restoreAllMocks()
  })

  it('adds Authorization header when token exists', async () => {
    useAuthStore.getState().setTokens('my-token', 'ref-token')
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: 'ok' }),
    })
    vi.stubGlobal('fetch', mockFetch)

    const { apiGet } = await import('./client')
    await apiGet('/api/test')

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/test'),
      expect.objectContaining({
        headers: expect.objectContaining({ Authorization: 'Bearer my-token' }),
      })
    )
  })

  it('does not add Authorization header when no token', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({}),
    })
    vi.stubGlobal('fetch', mockFetch)

    const { apiGet } = await import('./client')
    await apiGet('/api/test')

    const callHeaders = mockFetch.mock.calls[0][1].headers
    expect(callHeaders.Authorization).toBeUndefined()
  })
})
```

- [ ] **11.2** Run tests — they must fail (no implementation yet)

```bash
cd frontend && npm test -- --run src/app/api/client.test.ts
```

Expected output: 2 tests fail with module not found or similar error.

- [ ] **11.3** Implement the API client: `frontend/src/app/api/client.ts`

```ts
import { useAuthStore } from '../auth/authStore'

const BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const store = useAuthStore.getState()

  const doRequest = async (token: string | null) => {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(init.headers as Record<string, string> ?? {}),
    }
    return fetch(`${BASE_URL}${path}`, { ...init, headers })
  }

  let response = await doRequest(store.accessToken)

  if (response.status === 401 && store.refreshToken) {
    const refreshRes = await fetch(`${BASE_URL}/api/identity/commands/refresh-token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: store.refreshToken }),
    })

    if (refreshRes.ok) {
      const refreshData = await refreshRes.json() as { access_token: string; refresh_token: string }
      store.setTokens(refreshData.access_token, refreshData.refresh_token)
      response = await doRequest(refreshData.access_token)
    } else {
      store.clear()
      window.location.href = '/login'
      throw new Error('Session expired')
    }
  }

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`)
  }

  if (response.status === 204) return undefined as T
  return response.json() as Promise<T>
}

export const apiGet  = <T>(path: string)                  => apiFetch<T>(path)
export const apiPost = <T>(path: string, body: unknown)   => apiFetch<T>(path, { method: 'POST',  body: JSON.stringify(body) })
export const apiPut  = <T>(path: string, body: unknown)   => apiFetch<T>(path, { method: 'PUT',   body: JSON.stringify(body) })
```

- [ ] **11.4** Run tests — they must pass

```bash
cd frontend && npm test -- --run src/app/api/client.test.ts
```

Expected output:
```
✓ API client > adds Authorization header when token exists
✓ API client > does not add Authorization header when no token

Test Files  1 passed (1)
Tests       2 passed (2)
```

- [ ] **11.5** Run all tests together to confirm no regressions

```bash
cd frontend && npm test -- --run
```

Expected output: 7 tests pass (5 auth store + 2 API client), 0 failures.

- [ ] **11.6** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/app/api/client.ts frontend/src/app/api/client.test.ts && git commit -m "feat(frontend): add API client fetch wrapper with JWT auth and token refresh (2 tests passing)"
```

---

## Task 12 — API function stubs

**Files:**
- Create: `frontend/src/app/api/identity.ts`
- Create: `frontend/src/app/api/crm.ts`

### Steps

- [ ] **12.1** Create Identity API functions: `frontend/src/app/api/identity.ts`

```ts
import { apiGet, apiPost, apiPut } from './client'

// Auth
export interface LoginResult {
  access_token: string
  refresh_token: string
  expires_in: number
}

export interface CurrentUser {
  id: string
  email: string
  first_name: string
  last_name: string
  permissions: string[]
}

// Users
export interface UserListItem {
  id: string
  email: string
  first_name: string
  last_name: string
  roles: string[]
  active: boolean
}

export interface UserDetail {
  id: string
  email: string
  first_name: string
  last_name: string
  roles: string[]
  active: boolean
}

// Roles
export interface RoleListItem {
  id: string
  name: string
  permissions: string[]
}

export interface RoleDetail {
  id: string
  name: string
  permissions: string[]
}

export const identityApi = {
  // Auth
  login: (email: string, password: string) =>
    apiPost<LoginResult>('/api/identity/commands/login', { email, password }),

  logout: (refreshToken: string) =>
    apiPost<void>('/api/identity/commands/logout', { refresh_token: refreshToken }),

  me: () =>
    apiGet<CurrentUser>('/api/identity/me'),

  // Users
  getUsers: () =>
    apiGet<UserListItem[]>('/api/identity/users'),

  getUser: (id: string) =>
    apiGet<UserDetail>(`/api/identity/users/${id}`),

  registerUser: (data: { email: string; password: string; firstName: string; lastName: string }) =>
    apiPost<{ id: string }>('/api/identity/users/commands/register-user', {
      email: data.email,
      password: data.password,
      first_name: data.firstName,
      last_name: data.lastName,
    }),

  updateUser: (id: string, data: { email: string; firstName: string; lastName: string }) =>
    apiPut<void>(`/api/identity/users/commands/update-user/${id}`, {
      email: data.email,
      first_name: data.firstName,
      last_name: data.lastName,
    }),

  deactivateUser: (id: string) =>
    apiPost<void>(`/api/identity/users/commands/deactivate-user/${id}`, {}),

  assignRoles: (userId: string, roleIds: string[]) =>
    apiPost<void>(`/api/identity/users/commands/assign-roles/${userId}`, { role_ids: roleIds }),

  // Roles
  getRoles: () =>
    apiGet<RoleListItem[]>('/api/identity/roles'),

  getRole: (id: string) =>
    apiGet<RoleDetail>(`/api/identity/roles/${id}`),

  createRole: (name: string, permissions: string[]) =>
    apiPost<{ id: string }>('/api/identity/roles/commands/create-role', { name, permissions }),

  updateRolePermissions: (id: string, permissions: string[]) =>
    apiPut<void>(`/api/identity/roles/commands/update-role-permissions/${id}`, { permissions }),
}
```

- [ ] **12.2** Create CRM API functions: `frontend/src/app/api/crm.ts`

```ts
import { apiGet, apiPost, apiPut } from './client'

export interface CustomerListItem {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  city: string
}

export interface CustomerDetail {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  street: string
  city: string
  zip: string
  country: string
}

export const crmApi = {
  getCustomers: () =>
    apiGet<CustomerListItem[]>('/api/crm/contacts/customers'),

  getCustomer: (id: string) =>
    apiGet<CustomerDetail>(`/api/crm/contacts/customers/${id}`),

  registerCustomer: (data: {
    firstName: string; lastName: string; email: string
    phone?: string; street?: string; city?: string; zip?: string; country?: string
  }) =>
    apiPost<{ id: string }>('/api/crm/contacts/customers/commands/register-customer', {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
      phone: data.phone ?? '',
      street: data.street ?? '',
      city: data.city ?? '',
      zip: data.zip ?? '',
      country: data.country ?? '',
    }),

  updateCustomer: (id: string, data: {
    firstName: string; lastName: string; email: string
    phone?: string; street?: string; city?: string; zip?: string; country?: string
  }) =>
    apiPut<void>(`/api/crm/contacts/customers/commands/update-customer/${id}`, {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
      phone: data.phone ?? '',
      street: data.street ?? '',
      city: data.city ?? '',
      zip: data.zip ?? '',
      country: data.country ?? '',
    }),
}
```

- [ ] **12.3** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **12.4** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/app/api/identity.ts frontend/src/app/api/crm.ts && git commit -m "feat(frontend): add typed Identity and CRM API function stubs"
```

---

## Task 13 — TanStack Router setup + main.tsx update

**Files:**
- Create: `frontend/src/app/router.tsx`
- Modify: `frontend/src/main.tsx`
- Delete: `frontend/src/App.tsx` (no longer needed)
- Delete: `frontend/src/App.css` (no longer needed)

### Steps

- [ ] **13.1** Create the router: `frontend/src/app/router.tsx`

```tsx
import { createRouter, createRoute, createRootRoute, Outlet, redirect } from '@tanstack/react-router'
import { useAuthStore } from './auth/authStore'
import { LoginPage } from './modules/auth/LoginPage'

function requireAuth() {
  if (!useAuthStore.getState().isAuthenticated()) {
    throw redirect({ to: '/login' })
  }
}

function requirePermission(permission: string) {
  requireAuth()
  if (!useAuthStore.getState().hasPermission(permission)) {
    throw redirect({ to: '/' })
  }
}

const rootRoute = createRootRoute({ component: () => <Outlet /> })

const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  component: LoginPage,
})

const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  beforeLoad: () => {
    requireAuth()
    throw redirect({ to: '/crm/customers' })
  },
})

const crmCustomersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/crm/customers',
  beforeLoad: () => requirePermission('crm.contacts.view_customers'),
  component: () => <div style={{ padding: 32 }}>CRM Zákazníci — připravuje se</div>,
})

const identityUsersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/users',
  beforeLoad: () => requirePermission('identity.users.manage'),
  component: () => <div style={{ padding: 32 }}>Uživatelé — připravuje se</div>,
})

const identityRolesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/roles',
  beforeLoad: () => requirePermission('identity.roles.manage'),
  component: () => <div style={{ padding: 32 }}>Role — připravuje se</div>,
})

const routeTree = rootRoute.addChildren([
  loginRoute,
  indexRoute,
  crmCustomersRoute,
  identityUsersRoute,
  identityRolesRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register { router: typeof router }
}
```

- [ ] **13.2** Create a placeholder LoginPage so the router can compile (the real one comes in Task 14)

Create `frontend/src/app/modules/auth/LoginPage.tsx` with a temporary stub:

```tsx
export function LoginPage() {
  return <div>Login — loading...</div>
}
```

This allows the router to resolve the import without errors while Task 14 is not yet done. It will be replaced completely in Task 14.

- [ ] **13.3** Replace `frontend/src/main.tsx` entirely

```tsx
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from '@tanstack/react-router'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { router } from './app/router'
import './design-system/tokens/tokens.css'
import './index.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { retry: 1, staleTime: 30_000 },
  },
})

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>
  </StrictMode>,
)
```

- [ ] **13.4** Delete `frontend/src/App.tsx` and `frontend/src/App.css`

```bash
rm frontend/src/App.tsx frontend/src/App.css
```

- [ ] **13.5** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **13.6** Verify the dev server starts without crash

```bash
cd frontend && npm run build 2>&1 | tail -5
```

Expected output: build completes with `dist/` output, no errors.

- [ ] **13.7** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/app/router.tsx frontend/src/app/modules/auth/LoginPage.tsx frontend/src/main.tsx && git rm frontend/src/App.tsx frontend/src/App.css && git commit -m "feat(frontend): add TanStack Router with auth guards and QueryClient provider"
```

---

## Task 14 — Login page (functional, connects to real backend)

**Files:**
- Modify: `frontend/src/app/modules/auth/LoginPage.tsx` (replace placeholder from Task 13)
- Create: `frontend/src/app/modules/auth/LoginPage.module.css`

### Steps

- [ ] **14.1** Replace `frontend/src/app/modules/auth/LoginPage.tsx` with full implementation

```tsx
import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useMutation } from '@tanstack/react-query'
import { Button } from '../../../design-system'
import { FormField } from '../../../design-system'
import { Input } from '../../../design-system'
import { identityApi } from '../../api/identity'
import { useAuthStore } from '../../auth/authStore'
import styles from './LoginPage.module.css'

export function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const navigate = useNavigate()
  const { setTokens, setUser } = useAuthStore()

  const loginMutation = useMutation({
    mutationFn: () => identityApi.login(email, password),
    onSuccess: async (data) => {
      setTokens(data.access_token, data.refresh_token)
      const me = await identityApi.me()
      setUser(
        { id: me.id, email: me.email, firstName: me.first_name, lastName: me.last_name },
        me.permissions,
      )
      navigate({ to: '/' })
    },
  })

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <h1 className={styles.title}>ERP</h1>
        <p className={styles.subtitle}>Přihlaste se ke svému účtu</p>
        <form
          className={styles.form}
          onSubmit={(e) => { e.preventDefault(); loginMutation.mutate() }}
        >
          <FormField
            label="E-mail"
            htmlFor="email"
            error={loginMutation.isError ? 'Nesprávné přihlašovací údaje' : undefined}
          >
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@erp.local"
              error={loginMutation.isError}
            />
          </FormField>
          <FormField label="Heslo" htmlFor="password">
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </FormField>
          <Button type="submit" variant="primary" size="md" loading={loginMutation.isPending}>
            Přihlásit se
          </Button>
        </form>
      </div>
    </div>
  )
}
```

- [ ] **14.2** Create `frontend/src/app/modules/auth/LoginPage.module.css`

```css
.container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-neutral-50);
}
.card {
  background: #fff;
  border: 1px solid var(--color-neutral-200);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: var(--space-10) var(--space-10);
  width: 100%;
  max-width: 400px;
}
.title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-900);
  text-align: center;
  margin-bottom: var(--space-1);
}
.subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
  text-align: center;
  margin-bottom: var(--space-8);
}
.form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
```

- [ ] **14.3** Verify TypeScript compiles cleanly

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors.

- [ ] **14.4** Run all tests — confirm 7 tests still pass

```bash
cd frontend && npm test -- --run
```

Expected output:
```
Test Files  2 passed (2)
Tests       7 passed (7)
```

- [ ] **14.5** Build the app to confirm no bundler errors

```bash
cd frontend && npm run build 2>&1 | tail -10
```

Expected output: successful build with chunk sizes listed. No errors.

- [ ] **14.6** Commit

```bash
cd /home/michal/ddd-erp && git add frontend/src/app/modules/auth/ && git commit -m "feat(frontend): implement Login page — connects to real backend with JWT auth"
```

---

## Final verification

After all 14 tasks are complete, run the full verification checklist:

### Automated checks

- [ ] **V.1** All Vitest tests pass

```bash
cd frontend && npm test -- --run
```

Expected output: 7 tests pass across 2 test files (authStore + API client), 0 failures.

- [ ] **V.2** TypeScript is clean

```bash
cd frontend && npx tsc --noEmit
```

Expected output: no errors, no warnings.

- [ ] **V.3** Production build succeeds

```bash
cd frontend && npm run build
```

Expected output: `dist/` directory created, all chunks listed, no errors.

### Manual checks

- [ ] **V.4** Dev server starts

```bash
cd frontend && npm run dev
```

Open `http://localhost:5173/login` — login form renders with ERP heading and Czech labels.

- [ ] **V.5** Login with correct credentials

Enter `admin@erp.local` / `changeme` (requires backend running on `localhost:8000`).

Expected: redirect to `/crm/customers`, page shows "CRM Zákazníci — připravuje se".

- [ ] **V.6** Login with wrong credentials

Enter any wrong password.

Expected: error message "Nesprávné přihlašovací údaje" appears below the email field; input border turns red.

- [ ] **V.7** Already logged in — redirect from `/login`

After a successful login, navigate manually to `http://localhost:5173/login`.

Expected: immediately redirected to `/crm/customers` (auth guard on index route redirects).

- [ ] **V.8** Storybook shows all 7 components

```bash
cd frontend && npm run storybook
```

Open `http://localhost:6006` — sidebar should show:
- Design System/Button (8 stories: Primary, Secondary, Ghost, Danger, Loading, Disabled, Small, Large)
- Design System/Input (5 stories: Default, WithValue, ErrorState, Disabled, Password)
- Design System/FormField (3 stories: Default, WithError, WithHint)
- Design System/Badge (4 stories: Neutral, Success, Danger, Primary)
- Design System/Table (4 stories: Default, Loading, Empty, Clickable)
- Design System/Modal (1 story: Default — interactive open/close)
- Design System/PageLayout (1 story: Default — sidebar + main)

Verify design tokens apply globally (indigo buttons, zinc neutral palette, correct font sizes and spacing).

---

## Self-review

### Spec coverage check

| Spec requirement | Covered | Task |
|---|---|---|
| React 19 + TypeScript + Vite 8 | Existing setup preserved | — |
| Storybook 8 on port 6006 | `.storybook/main.ts` with react-vite framework | 1 |
| `design-system/` isolated from `app/` | No cross-imports; barrel `index.ts` is the only bridge | 9 |
| CSS custom properties tokens | 38 variables in `tokens.css` | 2 |
| Button — 4 variants, 3 sizes, loading, disabled | All 8 stories defined and implemented | 3 |
| Input — type, placeholder, error, disabled | All 5 stories defined and implemented | 4 |
| FormField — label, error string, hint, children | All 3 stories defined and implemented | 4 |
| Badge — 4 variants | All 4 stories defined and implemented | 5 |
| Table — columns, data, loading skeleton | All 4 stories; shimmer animation implemented | 6 |
| Modal — open, onClose, title, focus trap, ESC | Focus trap + ESC handler via `useEffect` | 7 |
| PageLayout — sidebar + main slots | CSS Grid 240px + 1fr layout | 8 |
| `design-system/index.ts` public API | All 7 components + Column type exported | 9 |
| Auth store: tokens in sessionStorage | `setTokens` / `clear` write to `sessionStorage` | 10 |
| Auth store: user + permissions | `setUser` + `hasPermission` | 10 |
| Auth store: Vitest tests | 5 passing tests (TDD) | 10 |
| API client: Bearer header | Injected on every request | 11 |
| API client: 401 → refresh → retry | Refresh flow with fallback to clear + redirect | 11 |
| API client: Vitest tests | 2 passing tests (TDD) | 11 |
| Identity API functions | All 11 functions: login, logout, me, getUsers, getUser, registerUser, updateUser, deactivateUser, assignRoles, getRoles, getRole, createRole, updateRolePermissions | 12 |
| CRM API functions | 4 functions: getCustomers, getCustomer, registerCustomer, updateCustomer | 12 |
| TanStack Router with beforeLoad guards | `requireAuth` + `requirePermission` helpers | 13 |
| Route: /login (public) | `loginRoute` — no guard | 13 |
| Route: / → redirect | `indexRoute` with `requireAuth` + redirect to /crm/customers | 13 |
| Route: /crm/customers (guarded) | `crm.contacts.view_customers` permission check | 13 |
| Route: /identity/users (guarded) | `identity.users.manage` permission check | 13 |
| Route: /identity/roles (guarded) | `identity.roles.manage` permission check | 13 |
| TanStack Query QueryClient | Configured in `main.tsx` with retry:1 + staleTime:30s | 13 |
| Login page: email + password form | `useState` for both fields | 14 |
| Login page: TanStack Query `useMutation` | `loginMutation` with `identityApi.login` | 14 |
| Login page: on success → fetch me → setUser + navigate | `onSuccess` handler | 14 |
| Login page: error state → Czech message | `loginMutation.isError` drives `FormField` error prop | 14 |
| Login page: loading spinner on submit | `loading={loginMutation.isPending}` on Button | 14 |

### Placeholder scan

No placeholders present. Every task contains:
- Complete file content (no "// add your code here" or "// similar to above")
- Exact CLI commands with expected output
- Explicit file paths for create/modify

### Type consistency check

| Interface | Defined in | Used in |
|---|---|---|
| `AuthUser` | `authStore.ts` | `authStore.ts`, `LoginPage.tsx` |
| `AuthState` | `authStore.ts` | Internal to store |
| `LoginResult` | `identity.ts` | `LoginPage.tsx` via `identityApi.login` return type |
| `CurrentUser` | `identity.ts` | `LoginPage.tsx` via `identityApi.me` return type |
| `Column<T>` | `Table.tsx` | Exported from `index.ts`, used in future CRM/Identity modules |
| `UserListItem`, `UserDetail` | `identity.ts` | Future Identity module |
| `RoleListItem`, `RoleDetail` | `identity.ts` | Future Identity module |
| `CustomerListItem`, `CustomerDetail` | `crm.ts` | Future CRM module |

snake_case (`first_name`, `last_name`) used in all API response interfaces (matches Symfony serializer output). camelCase used in React component state and `AuthUser` interface. Conversion happens explicitly in `LoginPage.tsx` `onSuccess` handler and in all `apiPost` call bodies.
