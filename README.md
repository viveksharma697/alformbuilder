# AI-Powered Form Builder

A full-featured form builder built with **Laravel 11**, **Livewire**, and **MySQL** — supporting manual drag-and-drop form creation, AI generation from natural language, and import from Word/Excel documents.

---

## Live Demo

**URL:** https://web-production-57f4d.up.railway.app

> **Demo credentials:**
> - Email: `demo@formbuilder.app`
> - Password: `password`

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 11 |
| Frontend | Livewire 3, Alpine.js, Tailwind CSS |
| Database | MySQL 8 / MariaDB 10.4+ |
| AI | OpenRouter / Llama 3.1 8B free (openai-php/laravel, OpenAI-compatible) |
| Queue | Laravel Database Queue |
| Excel | PhpSpreadsheet (maatwebsite/excel) |
| Word | PhpOffice/PhpWord |
| Auth | Laravel Breeze |

---

## Setup

### Prerequisites
- PHP 8.2+
- MySQL 8+ (or MariaDB 10.4+)
- Composer 2.x
- Node.js 18+ / npm
- OpenRouter API key (free — get one at https://openrouter.ai/keys)

> **Note:** If you have the PHP `psr` PECL extension installed, comment it out in `php.ini` (`extension=php_psr.dll`) — it conflicts with maatwebsite/excel on PHP 8.2.

### Installation

```bash
git clone <repo-url> formbuilder
cd formbuilder

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
DB_DATABASE=formbuilder
DB_USERNAME=root
DB_PASSWORD=your_password
OPENROUTER_API_KEY=sk-or-v1-your-key-here
```

```bash
mysql -u root -e "CREATE DATABASE formbuilder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan db:seed

# Start queue worker (AI generation + imports run async)
php artisan queue:work &

php artisan serve
```

Visit `http://localhost:8000`

---

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `DB_*` | MySQL connection | — |
| `OPENROUTER_API_KEY` | OpenRouter API key (free at openrouter.ai/keys) | — |
| `AI_BASE_URI` | AI provider base URI | `openrouter.ai/api/v1` |
| `AI_MODEL` | Model identifier | `meta-llama/llama-3.1-8b-instruct:free` |
| `QUEUE_CONNECTION` | Queue driver | `database` |
| `FORM_SUBMISSION_RATE_LIMIT` | Max submits/IP/hour | `10` |

---

## Architecture Overview

```
app/
├── Http/Controllers/
│   ├── FormController.php           CRUD + versioning (Part A, D)
│   ├── FormSubmissionController.php  Public fill, submit, CSV export (Part A)
│   ├── AiFormController.php         AI generation endpoints (Part B)
│   └── FormImportController.php     Word/Excel import (Part C)
├── Livewire/
│   └── FormBuilder.php              Real-time builder component (Part A)
├── Models/
│   ├── Form.php                     JSON schema + server-side validation
│   ├── FormSubmission.php
│   ├── AiGenerationJob.php          AI job tracking
│   ├── FormImport.php               Import state tracking
│   ├── FormVersion.php              Snapshot per save (Part D)
│   └── Webhook.php                  Per-form webhooks (Part D)
├── Services/
│   ├── FormSchemaValidator.php      Validate + repair AI-generated schemas
│   ├── AiFormService.php            OpenAI client + prompt logic
│   └── DocumentImportService.php   DOCX/XLSX deterministic parsing
└── Jobs/
    ├── GenerateAiFormJob.php        Queued AI generation with retries
    ├── ProcessFormImportJob.php     Queued document parsing
    └── DispatchWebhookJob.php       Queued webhook delivery with HMAC signing
```

---

## Database Schema / ERD Summary

### `forms`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → users | |
| `title` | varchar(200) | |
| `slug` | varchar(100) UNIQUE | Public fill URL identifier |
| `status` | enum | `draft`, `published`, `archived` |
| `schema` | JSON | **Single source of truth** |
| `settings` | JSON | Submit message, redirect URL |
| `version` | smallint | Auto-increments on every save |

**Indexes:** `(user_id, status)` for dashboard listing; `(slug)` for the hot public-fill path; `(status, accepts_submissions)` for filtering.

### `form_submissions`
| Column | Type | Notes |
|---|---|---|
| `form_id` | FK → forms | |
| `data` | JSON | key → response value |
| `files` | JSON | field_key → storage path |
| `ip_address` | varchar(45) | IPv6-safe |
| `form_version` | smallint | Schema version at submit time |

**Indexes:** `(form_id, created_at)` for paginated listing.

### `ai_generation_jobs`
Tracks every AI call: status, model, prompt/completion tokens, latency ms, retry count, error message.

**Indexes:** `(user_id, status)` for polling; `(form_id, status)` for form history.

### `form_versions` (Part D)
Full schema snapshot saved before every update. Enables rollback to any point in history.

### `webhooks` (Part D)
Per-form webhook config: URL, HMAC secret, event list, failure counter (auto-disabled after 10 failures).

---

## API Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/f/{slug}` | Public | Render public fill form |
| POST | `/f/{slug}` | Public | Submit response (rate limited) |
| GET | `/forms` | Auth | List forms |
| POST | `/forms` | Auth | Create form |
| GET | `/forms/{id}/builder` | Auth | Form builder UI |
| PUT | `/forms/{id}/schema` | Auth | Save schema (validates first) |
| GET | `/forms/{id}/submissions` | Auth | Paginated submissions + search |
| GET | `/forms/{id}/submissions/export` | Auth | Streamed CSV export |
| GET | `/forms/{id}/versions` | Auth | Version history |
| POST | `/forms/{id}/versions/{v}/restore` | Auth | Rollback to version |
| POST | `/ai/generate` | Auth | Queue AI form creation job |
| POST | `/ai/edit/{form}` | Auth | Queue AI form edit job |
| GET | `/ai/status/{jobId}` | Auth | Poll job status + result |
| POST | `/ai/apply/{form}` | Auth | Apply AI result schema to form |
| POST | `/imports/upload` | Auth | Upload .docx/.xlsx |
| GET | `/imports/{id}/status` | Auth | Poll import status |
| GET | `/imports/{id}/preview` | Auth | Review + adjust parsed schema |
| POST | `/imports/{id}/commit` | Auth | Finalize and create form |

---

## AI Prompt Strategy (Part B)

### System Prompt Design
The system prompt defines:
1. The exact JSON output contract (sections → fields with all required keys)
2. All 14 allowed field types with explicit per-type rules
3. Options format for choice fields (dropdown, radio, checkbox)
4. Validation shapes per type (min/max for number, min_length/max_length for text, etc.)
5. snake_case key requirement and uniqueness rule
6. Instruction to write practical placeholders and help text

The model is called with `response_format: json_object` to guarantee JSON output.

### Handling Hallucinated Field Types
`FormSchemaValidator::repair()` normalises any unknown type by inferring from the label:
- `email` → `email`, `phone/mobile` → `phone`, `date/birth` → `date`
- `description/comment/about` → `textarea`, `number/amount/age` → `number`
- fields with `options` array → `dropdown`, fallback → `text`

### Retries & Fallbacks
- `GenerateAiFormJob` retries up to 3 times with exponential backoff (Laravel default)
- On each attempt: parse JSON → validate schema → repair if needed → re-validate
- If schema is still invalid after repair: job marked `failed`, error stored
- Frontend polls `/ai/status/{jobId}` every 2 seconds

### AI Editing
When editing an existing form, the current schema JSON is embedded in the user message alongside the instruction. The model returns the complete updated schema (not a diff), eliminating merge complexity.

### Logging
Every AI call logs: `ai_job_id`, model name, total tokens (prompt + completion), latency ms.

---

## Part D — Own Ideas

See `DECISIONS.md` for full write-ups. Summary:

1. **Form Versioning & Rollback** — Every save snapshots the schema. One-click rollback from the history UI.
2. **Webhook Notifications** — Per-form webhooks with HMAC-SHA256 signing, event filters, auto-disable after failures.
3. **Rate Limiting & Spam Protection** — IP-based rate limiting on form submissions (configurable per-form). Laravel's `throttle` middleware on the submit route.

---

## Known Limitations

1. **OpenAI key required for AI features** — without it, generation jobs fail gracefully with a visible error.
2. **File uploads use local disk** — for production, configure `FILESYSTEM_DISK=s3` with S3 credentials.
3. **Queue worker must run separately** — `php artisan queue:work` in a separate process or supervisor.
4. **PHP `psr` extension** — must be disabled in php.ini if installed (conflicts with maatwebsite/excel type signatures on PHP 8.2).
5. **Conditional logic not in builder UI** — the schema supports a `conditions` key per field for future implementation.
