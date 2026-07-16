# Hallmark Web — PHP MVC prototype

A single-admin web application implementing the **Technical Report: Image
Processing & AI Automation Challenge** (PDF 2) for the jewelry hallmarking &
inventory system. An admin signs in, enters an item's UID + weight and uploads
one product photo, and the app generates the portal's standardised **3-pane
compliance composite** (UID · Model · Weight).

Built as a dependency-free **PHP MVC** app (PHP 8.1+, GD, SQLite) — no framework
or Composer install required.

## Features

- **Single admin login** — session auth, hashed password, CSRF-protected forms.
- **Composite generator** — GD engine that builds the 3-pane deliverable.
- **Dual track** (report §3), chosen automatically:
  - **Track A** — no UID close-up: deterministic metal-plate UID render.
  - **Track B** — upload a UID macro: it's placed into the UID pane.
- **Item history + dashboard** — every composite stored in SQLite with its
  track, provider and cost; dashboard totals the (billable) API cost.
- **Cost guard** — model-pane provider is capped at **< ₹1/image**; the paid
  tier falls back to the free path if unconfigured or over budget.

## Requirements

PHP 8.1+ with `gd` (FreeType), `pdo_sqlite`, `mbstring`. A DejaVu TrueType font
(path set in `config/config.php`). All present on most Linux/macOS PHP installs.

## Run

```bash
cd hallmark-web
php -S 127.0.0.1:8000 -t public
# open http://127.0.0.1:8000/
```

The SQLite database, schema and the admin account are created automatically on
first request.

**Default admin (prototype):** `admin` / `admin123`
(override with `HALLMARK_ADMIN_USER` / `HALLMARK_ADMIN_PASS`).

## MVC structure

```
public/
  index.php            Front controller (single entry point) + PSR-4 autoloader
  .htaccess            Apache rewrite to the front controller
  assets/css/app.css
config/
  config.php           Settings (admin, provider, cost ceiling, fonts)
  routes.php           Route table
src/
  Core/                Framework: App, Router, Request, Session, Database, View, Controller, Config
  Controllers/         AuthController, DashboardController, ItemController
  Models/              User, ComplianceItem   (PDO/SQLite)
  Services/
    ImageCompositor.php  GD engine — the 3-pane builder (Track B core)
    AiProvider.php       Track A model-pane provider + cost ceiling
  Support/Auth.php     Authentication for the single admin
  helpers.php          Global view helpers (e, csrf_field, money_inr)
views/
  layouts/ auth/ dashboard/ items/ partials/ errors/
database/schema.sql    SQLite schema
storage/               SQLite db, uploads, generated composites (gitignored)
```

Request flow: `public/index.php` → `Core\App` → `Core\Router` →
`Controller` → `Model` / `Service` → `View`.

## Routes

| Method | Path | Action |
|---|---|---|
| GET | `/login` | login form |
| POST | `/login` | authenticate |
| POST | `/logout` | sign out |
| GET | `/` | dashboard (auth) |
| GET | `/items` | item list (auth) |
| GET | `/items/new` | new-composite form (auth) |
| POST | `/items` | generate composite (auth) |
| GET | `/items/{id}` | view composite (auth) |
| GET | `/items/{id}/composite` | stream the PNG (auth) |

## Configuration (env vars)

| var | default | purpose |
|---|---|---|
| `HALLMARK_ADMIN_USER` / `HALLMARK_ADMIN_PASS` | `admin` / `admin123` | seeded admin |
| `HALLMARK_AI_PROVIDER` | `passthrough` | `passthrough` (₹0) or `flux` (~₹0.25) |
| `HALLMARK_MAX_COST_INR` | `1.0` | per-image cost ceiling |
| `HALLMARK_BILL_CLIENT` | `true` | bill API cost to the client (report §4) |
| `FAL_KEY` | — | needed only for the `flux` tier |

## Why UID & weight are never AI-generated

The UID is an etched string and the weight is a measured number — both are
*facts* required for portal compliance. Generative models render exact
text/digits unreliably, so a hallucinated value would fail compliance. Those two
panes are therefore drawn deterministically with GD; only the model/product pane
can use a paid model (kept under ₹1/image). This mirrors the Python engine in
the repository root, ported to PHP GD.

## Scope

Covers PDF 2 (image processing & automation) with a single admin, per request.
Multi-tenant admin, receipts, OTP and vendor profiles from the meeting summary
are out of scope for this prototype.
