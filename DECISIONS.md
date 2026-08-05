# DECISIONS.md — AI Form Builder

## Assumptions

1. **MySQL compatibility**: The system runs on MariaDB 10.4 locally (XAMPP). The schema is MySQL 8 compatible — `JSON_SEARCH` for submission search, `JSON` column type. No MariaDB-specific features used.

2. **Queue driver**: Using `database` queue driver rather than Redis/Horizon because the environment doesn't have Redis running. The architecture is Horizon-ready — just change `QUEUE_CONNECTION=redis` and run `php artisan horizon`.

3. **Authentication**: Single-user scope (each user sees only their own forms). Multi-tenancy would add an `organization_id` FK and row-level scoping — documented below.

4. **AI provider**: OpenRouter with `meta-llama/llama-3.1-8b-instruct:free` as default — zero-cost model accessible via a free OpenRouter API key. The client uses the OpenAI-compatible SDK (`openai-php/laravel`) pointed at `openrouter.ai/api/v1`, so swapping to any other provider (OpenAI GPT-4o-mini, Anthropic Claude, etc.) is a one-line env change. Because free models don't support `response_format: json_object`, the system prompt explicitly instructs the model to return raw JSON only, and `parseAndRepairSchema()` strips markdown code fences defensively.

5. **File storage**: Local disk by default. Production deployment should use S3 — just set `FILESYSTEM_DISK=s3` and add S3 credentials. No code changes needed.

6. **Form slug uniqueness**: Generated from title with a numeric suffix if taken (`my-form`, `my-form-1`, etc.). Slugs never change after creation to avoid breaking live links.

---

## Part D — Own Ideas

### 1. Form Versioning & Rollback

**User problem:** Form owners accidentally delete a field or corrupt the schema during editing, with no way to recover.

**Implementation:**
- `form_versions` table stores a complete schema snapshot before every save
- `version` integer on `forms` table auto-increments on each save
- Builder auto-saves on every "Save" or "Publish" action
- Versions list at `/forms/{id}/versions` with one-click restore
- Restore itself creates a new version snapshot (so you can undo a restore)

**Trade-offs accepted:**
- Full schema snapshots rather than diffs — simpler to implement and query, slightly more storage. At typical form sizes (~10KB JSON), 100 versions = 1MB per form. Acceptable.
- No named/tagged versions beyond auto-labels — a "create checkpoint" button would be the next feature.

**What I'd do with more time:** Diff view between two versions (JSON diff rendered as a human-readable field comparison), named checkpoints, and auto-cleanup of versions older than 90 days.

---

### 2. Webhook Notifications (Part D)

**User problem:** Teams want to trigger Zapier, Slack, or custom integrations when a form is submitted, without polling the submissions API.

**Implementation:**
- `webhooks` table: one or more webhook URLs per form, each with a configurable event list
- `DispatchWebhookJob` queued on every submission, sending JSON payload to the URL
- HMAC-SHA256 signature header (`X-Form-Builder-Signature`) using a per-webhook secret
- Auto-disable after 10 consecutive delivery failures (with `failure_count` tracking)
- Configurable event list (`submission.created` today; extensible to `form.published`, etc.)

**Trade-offs accepted:**
- No retry delay backoff — retries happen immediately on the next queue pick-up. For production, I'd use exponential backoff with `$this->release(60 * (2 ** $this->attempts()))`.
- Webhook configuration only through the database/API — no UI built yet (time constraint).

**What I'd do with more time:** Delivery log (last N webhook calls with status + response body), UI for webhook management, and a "test delivery" button.

---

### 3. Rate Limiting & Spam Protection

**User problem:** Public forms are vulnerable to bot spam, flooding the submissions table and making real responses hard to find.

**Implementation:**
- Laravel's `throttle:30,1` middleware on the public submit route (30 requests per minute per IP)
- Application-level per-form rate limiting in `FormSubmissionController::submit()`: max 10 submissions per IP per hour per form (configurable via `FORM_SUBMISSION_RATE_LIMIT`)
- Stored in Laravel's database cache: key = `form_submit:{form_id}:{ip}`
- Graceful degradation: rate-limited users see an error message rather than a 429 page

**Trade-offs accepted:**
- IP-based rate limiting is bypassable with proxies. A proper anti-spam solution would add CAPTCHA (hCaptcha or Cloudflare Turnstile). IP limiting is a solid first layer.
- Per-form rate limit uses database cache, not Redis — adds a small DB hit per submission. With Redis this is a 1-microsecond operation.

**What I'd do with more time:** Honeypot field injection (hidden field that bots fill and humans don't), CAPTCHA integration, and a spam flag system where admins can mark and bulk-delete spam submissions.

---

## Trade-offs Accepted Overall

| Decision | Trade-off |
|---|---|
| JSON schema in single `schema` column | Simpler queries vs. indexed field searches. At scale, consider `form_fields` table with JSON validation column |
| Full schema snapshots for versions | Simple implementation vs. storage growth. Acceptable at form sizes |
| Database queue driver | Works with no extra infrastructure vs. Redis/Horizon for higher throughput |
| Livewire for builder | Real-time reactivity with server round-trips vs. a pure SPA (React). Livewire is simpler to deploy and maintain for this use case |
| PHP `psr` extension workaround | Had to disable it in php.ini due to maatwebsite/excel incompatibility on PHP 8.2 — not ideal for shared hosting |

---

## What I'd Build With Two More Weeks

1. **Conditional Logic / Branching**: Show/hide fields based on other field values. The schema already supports a `conditions` key per field — the builder UI and fill form would evaluate `[{field: "position", operator: "equals", value: "manager"}]` chains.

2. **Multi-tenant Isolation**: Add `organization_id` to `forms` and `users`. Scope all queries to the organization. Row-level security at the database level.

3. **Template Library**: Pre-built form templates (NPS survey, job application, event registration) that users can clone and customize. Store as seeded forms with `is_template = true`.

4. **Redis-cached Compiled Schemas**: Cache `form.buildValidationRules()` in Redis with TTL. Invalidate on form save. This makes the hot path (form submission) a cache hit instead of JSON parsing.

5. **Completion Analytics**: Track form open events vs. completions. Show drop-off by field (which field do people abandon on). Store as a lightweight `form_events` table with `event_type` and `field_key`.

6. **Python FastAPI AI Service**: Move AI generation to a separate Python microservice (as mentioned in the brief as a positive signal). The Laravel app calls it over REST. This allows switching LLM providers, fine-tuning, and independent scaling.

7. **Docker + CI**: Dockerfile, docker-compose.yml (MySQL + queue worker + web), and GitHub Actions CI running migrations + Pest tests on every PR.

8. **Pest Test Suite**: Feature tests for form CRUD, submission validation, AI job lifecycle, and import parsing. Currently only manual testing.
