# TinyBoard 🧩

TinyBoard is a lightweight Kanban app built with **Slim (PHP)** + **Next.js**. It ships with internal-only auth, API keys for external access, and a clean drag-and-drop UI.

## ✨ Highlights

- ✅ Slim 4 + SQLite backend
- ✅ Next.js App Router frontend
- ✅ Internal auth via server-only API key
- ✅ External API access via `X-API-Key`
- ✅ Swagger docs + full test suites

## 🧭 Quick Start (Both Services)

From the repo root:

```bash
docker compose up --build
```

Then open:

- Frontend: `http://localhost:3000`
- Backend: `http://localhost:8080`
- Swagger: `http://localhost:8080/docs`

## ⚙️ Environment Files

The root compose file uses:

- `.env.backend`
- `.env.frontend`

These are already in the repo and ready for local use.

## 🔧 Run Services Individually

### Backend

```bash
cd tinyboard-backend
docker compose up --build
```

### Frontend

```bash
cd tinyboard-frontend
pnpm dev
```

Or with Docker:

```bash
cd tinyboard-frontend
docker compose -f compose.yml up --build
```

## 🧪 Tests

### Backend

```bash
cd tinyboard-backend
docker compose exec php composer test
```

### Frontend

```bash
cd tinyboard-frontend
pnpm test
```

## 🔐 Auth Model (Quick Notes)

- Internal auth endpoints (`/auth/*`) require `X-Internal-API-Key`
- API endpoints require `X-Session-Token` or `X-API-Key`

## 📁 Repository Layout

```
tinyboard-backend/   Slim API + SQLite
tinyboard-frontend/  Next.js UI
compose.yml          Runs both services together
```

---

TinyBoard — small, sharp, and automation-ready ⚡

## 🚀 Production Compose

Use the production compose file (multi-stage builds + Nginx + Next standalone):

```bash
docker compose -f compose.prod.yml up --build
```

Production env files:

- `.env.backend.prod`
- `.env.frontend.prod`
