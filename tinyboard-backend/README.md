# TinyBoard Backend 

Welcome to **TinyBoard** 🧩 — a minimal, reliable Kanban API built with Slim Framework + SQLite.

## 🌟 Highlights

- ✅ Slim 4 + PHP 8.3
- ✅ SQLite for fast local development
- ✅ Internal-only auth endpoints (protected by internal API key + host allowlist)
- ✅ External API access via `X-API-Key`
- ✅ Full test suite via PHPUnit

## 🧭 Quick Start

### 1) Run the API (Docker)

```bash
docker compose up --build
```

API will be available at:

```
http://localhost:8080
```

### 2) Run tests

```bash
docker compose exec php composer test
```

## 🔐 Auth Model (Important)

TinyBoard has **two types of authentication**:

### ✅ Internal (UI-only)

Auth endpoints (register/login/logout/me) **require internal access**:

- `X-Internal-API-Key` header
- Host must match `INTERNAL_API_ALLOWED_HOSTS`

### ✅ External (API clients)

All other endpoints accept:

- `X-API-Key` (generated per user in the Profile section)

## 🌍 CORS

Local UI origin is allowed by default:

```
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

## ⚙️ Environment Variables

Create `.env` (already provided in this repo):

```
APP_DEBUG=true
DB_PATH=/var/www/var/database.sqlite
INTERNAL_API_KEY=abcd-1234-5678-efgh
INTERNAL_API_ALLOWED_HOSTS=localhost,127.0.0.1,app.myboard.localhost
CORS_ALLOWED_ORIGINS=http://localhost:3000
SESSION_TTL_HOURS=720
```

## 📚 API Documentation

Swagger UI:

```
http://localhost:8080/docs
```

OpenAPI JSON:

```
http://localhost:8080/openapi.json
```

## 📁 Project Structure

```
app/                Slim bootstrap
src/                Actions, middleware, repositories
public/             Entry point + Swagger UI
tests/              PHPUnit tests
```

## 🧪 Troubleshooting

- **401 on /auth/** → Internal key missing or invalid
- **CORS blocked** → Ensure `CORS_ALLOWED_ORIGINS` is set
- **DB issues** → Check `DB_PATH` and volume permissions

---

TinyBoard Backend — small, fast, and ready for automation ⚡
