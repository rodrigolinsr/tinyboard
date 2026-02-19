# TinyBoard Frontend 🎛️

Welcome to **TinyBoard** — a clean, fast Kanban UI powered by Next.js, Tailwind, and shadcn/ui.

## ✨ What You Get

- ✅ Elegant, spacious layout with full-width boards
- ✅ Drag & drop tasks (with ghost overlay)
- ✅ Profile-based API key management
- ✅ Internal auth routed through Next.js API handlers
- ✅ Tests with Vitest + RTL

## 🚀 Run Locally

### 1) Environment

Create `.env.local` (already in repo):

```
API_BASE_URL=http://localhost:8080
INTERNAL_API_KEY=abcd-1234-5678-efgh
NEXT_PUBLIC_API_BASE_URL=http://localhost:8080
```

### 2) Start Dev Server

```bash
pnpm dev
```

App will be available at:

```
http://localhost:3000
```

### 3) Run Tests

```bash
pnpm test
```

## 🐳 Run with Docker

```bash
docker compose -f compose.yml up --build
```

## 🧠 How Auth Works

- UI uses **Next.js API routes** to talk to `/auth/*`
- Internal API key stays server-only
- After login, session tokens are stored in localStorage

## 🗂️ Useful Folders

```
app/                Next.js routes and UI
src/components/     shadcn components
src/lib/            API client + state
```

## 🎨 UI Notes

- Squared aesthetic (subtle corners only)
- Spacious layout, board takes priority
- Sidebar collapsible for focus mode

---

TinyBoard Frontend — fast, sharp, and focused ✨
