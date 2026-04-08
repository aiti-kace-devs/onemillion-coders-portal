# Student partner progress — architecture and workflows

This document explains, in **plain language**, how OMCP shows **partner learning progress** to staff and students: what happens behind the scenes, what the main technical words mean, and how configuration ties it together.

For day-to-day operations (signing, env, probes), see `docs/partner-progress-runbook.md`.

---

## Partner Integrations admin — partner code field

On **create/edit**, **Partner Code** is a **dropdown** of distinct **`programmes.provider`** values (normalized slugs) plus **Other (enter manually)**. Choosing **Other** reveals the **custom** text field with the same validation rules as before (lowercase slug). Path/query placeholders such as `{omcp_id}` remain **partner-specific** and are configured under **Endpoints** and **Path param bindings** JSON.

---

## 1. What this feature is for (plain language)

**Goal:** Show how far a learner has progressed on a **partner’s** learning platform (videos, quizzes, labs, projects, etc.) **inside OMCP**, without opening the partner site.

**Important idea:** The **screens you click** (admin student page, student Progress page) mostly read **data already saved in OMCP’s database**. They do **not** call the partner on every page load. Partner APIs are used when a **sync** runs (background job or refresh), and the results are **stored** first—then the UI reads those stored rows.

---

## 2. Glossary — words and ideas you will see in code and tickets

| Term | Simple meaning |
|------|----------------|
| **Partner** | The external training platform (e.g. Startocode) that owns the learner’s course activity. |
| **`partner_code`** | A short, stable id (e.g. `startocode`) that must match the programme **provider**, **Partner integration**, **course mapping**, and saved progress rows. |
| **Sync** | An operation that **calls the partner API**, turns the response into OMCP’s internal shape, and **writes** `student_partner_progress` (and sometimes history). |
| **Snapshot** | One **current** row in `student_partner_progress` for a given learner + partner + **course**. It holds the latest percentages, JSON summaries, sync times, staleness, reminder times. |
| **History** | Rows in `student_partner_progress_history` used for **charts over time**. Not every sync creates a point—only when rules say progress changed or enough time passed (see config). |
| **Normalize / normalization** | Turning the partner’s JSON into a **fixed internal structure** (units, `*_percentage_complete` metrics, etc.) so OMCP can store and compare it regardless of vendor quirks. |
| **Hybrid (data flow)** | **Ingestion** is from the partner (on sync). **Display** is from the **database** (snapshot + history). So: partner feeds the DB; UI reads the DB. |
| **Eligibility** | Whether this learner’s **course** is mapped to this partner and an integration is enabled—if not, progress is hidden or “not eligible.” |
| **`learning_path_id`** | A partner-specific id for a **track** or unit in their payload. OMCP uses it to pick the right “slice” of progress when several exist. |
| **Stale / staleness** | Progress is treated as **stale** after `stale_after_at`. That timestamp is computed when a snapshot is saved (from last activity + “stale days,” or from sync time when activity is missing—see code). |
| **Cooldown (reminder)** | Minimum **hours between reminder emails** for the **same** snapshot after one was already sent. Prevents emailing the learner every hour while they remain stale. |
| **External learner key** | The id OMCP sends to the partner to identify the learner (`userId`, `student_id`, or numeric `id` depending on partner—see runtime helpers). Stored on the snapshot as `omcp_id` (name is legacy). |
| **Driver** | Pluggable code that knows how to talk to a partner (`StartocodeProgressDriver`, or a **generic** driver driven by DB integration + mapping JSON). |
| **`selected` metrics** | Inside `progress_summary_json`, the map of `*_percentage_complete` keys (and related flags) chosen for the learner—what bars and emails use for “current” breakdown. |
| **Refresh / preview** | Loading a page may **queue** a background sync; the page still shows the **last saved** snapshot until the job finishes. |

---

## 3. End-to-end workflow (simple steps)

### A. Setup (once per environment / partner)

1. **Programme** has a **provider** slug that matches **`partner_code`**.
2. **Partner integration** row exists (base URL, auth, endpoints, optional signing, response mapping).
3. **Partner–course mapping** links the OMCP **course** (or a name pattern) to that partner and optional **`learning_path_id`**.
4. Optional **App config** keys (see §9) tune stale days, reminder cooldown, etc.

### B. When progress is fetched (sync)

1. OMCP decides **eligibility** (registered course + mapping + enabled integration).
2. A **driver** calls the partner **HTTP API** (single student or bulk program page).
3. The response is **normalized** to internal **units** and metrics.
4. **`pickProgressUnit`** chooses the right unit (e.g. by `learning_path_id`).
5. **`persistSnapshot`** upserts **`student_partner_progress`** for `(user, partner_code, course_id, …)` with `progress_summary_json`, `overall_progress_percent`, **`stale_after_at`**, sync timestamps.
6. **`appendHistoryPointIfNeeded`** may insert a **history** row for charts when overall/metrics changed or a **minimum gap** passed (`PARTNER_PROGRESS_HISTORY_MIN_GAP_HOURS` / services config).

### C. When someone opens the UI (admin or student)

1. **`getSnapshotForPreview`** loads the **latest snapshot** from the DB for the learner’s mapping (and may **queue** `RefreshPartnerProgressJob`—it does **not** wait for the partner inline for the HTTP response).
2. **History** for charts comes from `StudentPartnerProgressHistory::combinedSeriesForSnapshot` (optionally mixed with rollups when retention is on).
3. **`PartnerProgressVisualizationService::buildStudentProgressPayload`** builds **activities** (metric keys + labels + colours + current %) from **`selected`**, else from history metrics, else a small default set.
4. **Admin** Blade + **student** Vue charts use that structure; they show **stored** data. If the last sync saved **empty** metrics (partner returned empty arrays), **current** bars can be zero even if older history had points.

### D. Stale reminders (email)

1. Scheduled command **`partner:send-stale-progress-reminders`** runs **hourly** (Laravel scheduler; server **cron** must call `schedule:run` every minute).
2. Rows with `stale_after_at <= now()` are candidates.
3. **`PartnerProgressStalenessService::shouldSendReminder`** requires: still stale, email present, and either **no prior reminder** or **cooldown elapsed** since `last_reminder_sent_at`.
4. On success, **`last_reminder_sent_at`** and **`reminder_count`** update. Email content uses the **saved snapshot** (`progress_breakdown` lists all `*_percentage_complete` keys in the template; legacy placeholders still exist).

---

## 4. “Why is the UI empty but the API returned 200?”

- **HTTP 200** only means the call worked. The body can still have **empty** `learning_paths` / `courses`, which normalizes to **no metrics** and **`selected` = []**.
- Progress is **per course**: changing **`registered_course`** or mappings can point the UI at a **different** snapshot row than an older seeded or test row.
- Charts read **DB** snapshot + history, not the raw response from that moment.

---

## 5. Core building blocks (concise)

| Concept | Meaning |
|--------|--------|
| **`PartnerIntegration`** | One row per partner: base URL, auth, headers, signing, endpoints, response mapping. |
| **`PartnerCourseMapping`** | Links a **course** (or name pattern) to a partner and optional **`learning_path_id`**. |
| **`PartnerProgressDriver`** | Contract: fetch one student, fetch bulk page, normalize. Implemented by a **named** driver (e.g. Startocode) or **generic** driver from DB config. |
| **Snapshot** | `student_partner_progress` — latest state for learner + partner + course. |
| **History** | `student_partner_progress_history` — time series for charts. |

---

## 6. Eligibility

`PartnerCourseEligibilityService` checks **registered course** (or latest admission), programme **provider** = **`partner_code`**, active **mapping**, and an **enabled** integration with **base_url**.

---

## 7. Driver registry

- **Bundled drivers** (e.g. `StartocodeProgressDriver`).
- **Generic driver**: enabled **`partner_integrations`** row + `GenericProgressDriverFactory` + `ProgressMappingNormalizer` + defaults in `config/services.php`.

---

## 8. Single-student sync (`PartnerProgressSyncService::syncUser`)

1. Resolve mapping → partner, course, `learning_path_id`.
2. Resolve **external learner key** (`User::partnerProgressExternalIdentifier()`).
3. Optional **`updated_since`** from last sync for incremental API calls.
4. `driver->fetchStudentProgress` → HTTP via `PartnerProgressClient`.
5. On failure → `saveSyncFailure` (`last_sync_error`).
6. On success → normalize → validate optional contract → **`pickProgressUnit`** → overall %, **`stale_after_at`**, **`persistSnapshot`**, optional **history** append.

---

## 9. Staleness, reminders, and App config

### Stale days

- **`stale_after_at`** is set when the snapshot is saved (from **last activity + stale days**, or from **sync time + stale days** when activity is absent—see `PartnerProgressSyncService`).
- **Admin UI:** **App config** key **`PARTNER_PROGRESS_STALE_AFTER_DAYS`** (integer, cached). If absent, falls back to **`services.partner_progress.stale_after_days`** / env **`PARTNER_PROGRESS_STALE_AFTER_DAYS`** (default **3**).

### Reminder cooldown

- After a reminder is sent, **`last_reminder_sent_at`** is set. Another email is only considered after **`reminder_cooldown_hours`** have passed **and** the row is still stale.
- **Admin UI:** **App config** **`PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS`** (integer, cached). If absent, **`services.partner_progress.reminder_cooldown_hours`** / env **`PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS`** (default **24**).

### Sending reminders

- Command: **`partner:send-stale-progress-reminders`** (hourly).
- Can be disabled via **`PARTNER_PROGRESS_SEND_STALE_REMINDERS`** / `services.partner_progress.send_stale_reminders`.
- **Scheduler:** production needs **cron** invoking `php artisan schedule:run` every minute; reminders are **not** sent by a long-running “reminder daemon” by themselves.

### External learner id (Startocode)

For **`partner_code` = `startocode`**, single-student sync prefers **`users.student_id`** when set; otherwise **`users.id`**. Other partners typically use **`users.userId`**. Bulk resolution follows the same ideas.

---

## 10. Preview and refresh

`getSnapshotForPreview` loads the DB snapshot and may dispatch **`RefreshPartnerProgressJob`** (`syncUser`, queued, rate-limited). Admin **Manage Student** can trigger refresh via the partner-progress refresh route.

---

## 11. HTTP layer (`PartnerProgressClient`)

Resolves `PartnerIntegration`, builds paths from `endpoints_json`, merges headers, optional **`PartnerIntegrationRequestSigner`**. Header merge behaviour avoids corrupting duplicate partner keys.

---

## 12. Visualization (admin + student)

- **Service:** `PartnerProgressVisualizationService::buildStudentProgressPayload` — builds **`activities`** (dynamic metric keys from **`selected`**, else history, else defaults) and **history** rows for charts.
- **Admin:** `ManageStudentCrudController` passes snapshot, history, and server-built **activities** into `manage_student_show` charts.
- **Student:** Inertia **`Progress.vue`** consumes the same payload shape.
- Charts use **Chart.js**; theme colours follow **admin/student UI theme** (not OS dark-mode alone), so axes and legends stay readable on light and dark skins.

---

## 13. Bulk / program sync

- `partner:sync-program-progress` → `SyncProgramProgressPageJob` → `fetchProgramProgressPage` → per item `syncBulkItem`.
- Pagination re-dispatches while `has_more`.
- `PartnerIntegrationObserver` may queue first page when an integration is saved enabled.

---

## 14. Scheduler (summary)

| Frequency | Behaviour |
|-----------|-----------|
| Hourly | Stale progress reminders |
| Hourly | `partner:sync-program-progress` per registered partner (incremental window) |
| Every 15 min | `partner:monitor-sync-health` |
| Daily | `partner:prune-history` |

Queue workers process `RefreshPartnerProgressJob`, `SyncProgramProgressPageJob`, etc.

---

## 15. Related tables — columns and purpose

The following reflects Laravel migrations. **MySQL** is assumed for generated columns (e.g. `course_id_coalesced`). Confirm compatibility if you use another engine.

### 15.1 `partner_integrations`

One row per external partner API configuration (Backpack **Partner integrations**).

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Surrogate key |
| `partner_code` | string(64), unique | Normalized slug; must match `partner_course_mappings.partner_code`, programme `provider`, and drivers |
| `display_name` | string(120) | Human label in admin |
| `is_enabled` | boolean, indexed | When false, eligibility checks ignore this integration |
| `refresh_timestamp_header_without_signing` | boolean | If true, refresh unix timestamp headers without HMAC (only when not using signing scheme) |
| `base_url` | string, nullable | Partner API origin |
| `auth_type` | string(40) | `none`, `bearer_token`, `api_key_header`, `basic`, `custom` |
| `auth_config_json` | json, nullable | Auth secrets/structure |
| `headers_json` | json, nullable | Extra static headers; may hold `ps_…` for derived signing on some partners |
| `signature_config_json` | json, nullable | HMAC/signing scheme |
| `endpoints_json` | json, nullable | `single_progress` / `bulk_progress` paths, `query_map`, `skip_updated_since` |
| `path_param_bindings_json` | json, nullable | How `{placeholders}` in paths resolve |
| `response_mapping_json` | json, nullable | Generic driver: where to read partner payload fields |
| `pagination_mapping_json` | json, nullable | Reserved: bulk pagination field paths |
| `metrics_mapping_json` | json, nullable | Reserved: metrics paths |
| `validation_contract_json` | json, nullable | Optional rules for normalized payloads |
| `rate_limit_per_minute` | unsigned int, nullable | Soft cap for outbound requests |
| `timeout_seconds` | unsigned int, nullable | HTTP timeout |
| `retry_attempts` | unsigned tinyint, nullable | Max HTTP retries |
| `retry_backoff_ms_json` | json, nullable | Backoff delays between retries (ms) |
| `notes` | text, nullable | Staff notes |
| `created_at`, `updated_at` | timestamps | Audit |

---

### 15.2 `partner_course_mappings`

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Surrogate key |
| `partner_code` | string(64), indexed | Must match `partner_integrations.partner_code` |
| `course_id` | FK → `courses.id`, nullable | Exact course match when set |
| `course_name_pattern` | string, nullable | Substring match on course name |
| `learning_path_id` | unsigned bigint, nullable | Partner track / unit id for `pickProgressUnit` |
| `is_active` | boolean, indexed | Inactive mappings are ignored |
| `meta_json` | json, nullable | Extensibility |
| `created_at`, `updated_at` | timestamps | Audit |

---

### 15.3 `programmes` (relevant columns)

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Programme id |
| `provider` | string, nullable | Partner slug; used with eligibility and Partner Code dropdown |

---

### 15.4 `student_partner_progress`

**Current snapshot** per learner + partner + course (unique combination).

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Snapshot id |
| `user_id` | FK → `users.id` | OMCP learner |
| `partner_code` | string(64), indexed | Partner |
| `omcp_id` | string, indexed | Stored external learner key (name is legacy) |
| `course_id` | FK → `courses.id`, nullable | Course for this row |
| `course_id_coalesced` | stored generated (MySQL) | `IFNULL(course_id,0)` for uniqueness |
| `learning_path_id` | unsigned bigint, nullable | From mapping; used when interpreting payload |
| `partner_student_ref` | string, nullable | Partner’s student reference if exposed |
| `progress_summary_json` | json, nullable | Summary including **`selected`** metrics |
| `progress_raw_json` | json, nullable | Raw/normalized debug snapshot |
| `overall_progress_percent` | decimal(5,2), nullable | Rolled-up percentage |
| `last_activity_at` | timestamp, nullable, indexed | Drives staleness input |
| `last_synced_at` | timestamp, nullable, indexed | Last successful API sync |
| `last_sync_attempt_at` | timestamp, nullable | Last attempt (success or failure) |
| `stale_after_at` | timestamp, nullable, indexed | Stale threshold for UI/reminders |
| `last_reminder_sent_at` | timestamp, nullable, indexed | Cooldown anchor for emails |
| `reminder_count` | unsigned int | Reminders sent count |
| `last_sync_error` | string, nullable | Last sync error message |
| `created_at`, `updated_at` | timestamps | Audit |

**Uniqueness:** unique on `(partner_code, omcp_id, course_id_coalesced)`.

---

### 15.5 `student_partner_progress_history`

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | History row id |
| `student_partner_progress_id` | FK → `student_partner_progress.id` | Parent snapshot |
| `user_id` | FK → `users.id` | Denormalized |
| `partner_code` | string(64), indexed | Partner |
| `course_id` | FK → `courses.id`, nullable | Course context |
| `captured_at` | timestamp, indexed | When recorded |
| `overall_progress_percent` | decimal(5,2), nullable | Overall at capture |
| `video_*` / `quiz_*` / … | decimal, nullable | Legacy columns; may be null |
| `payload_json` | json, nullable | **`selected_metrics`**, `raw` — preferred for charts |
| `created_at`, `updated_at` | timestamps | Audit |

---

### 15.6 `student_partner_progress_history_rollups`

Optional aggregated buckets for long retention. May be empty until populated.

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Rollup row id |
| `student_partner_progress_id` | FK | Snapshot |
| `user_id` | FK | Learner |
| `partner_code` | string(64), indexed | Partner |
| `course_id` | FK, nullable | Course |
| `period_date` | date, indexed | Bucket date |
| `granularity` | string(16) | e.g. `daily` |
| `last_captured_at` | timestamp, indexed | Latest source timestamp in bucket |
| `overall_progress_percent` | decimal(5,2), nullable | Aggregated overall |
| `metrics_json` | json, nullable | Aggregated metrics |
| `created_at`, `updated_at` | timestamps | Audit |

**Uniqueness:** `(student_partner_progress_id, period_date, granularity)`.

---

### 15.7 `partner_progress_sync_audits`

Diagnostics for bulk/contract issues (not one row per successful student sync).

| Column | Type (approx.) | Purpose |
|--------|----------------|---------|
| `id` | bigint, PK | Audit row id |
| `partner_code` | string(50), indexed | Partner |
| `context` | string(120), nullable, indexed | e.g. program slug |
| `omcp_id` | string(100), nullable, indexed | Learner key when known |
| `reason` | string(120), indexed | Reason code |
| `payload_json` | json, nullable | Context |
| `created_at`, `updated_at` | timestamps | Audit |

---

### 15.8 `app_configs` (partner progress keys)

Backpack **App config** / system settings. Values load into Laravel `config()` at boot (cached when `is_cached` is true).

| Key | Typical type | Purpose |
|-----|--------------|---------|
| **`PARTNER_PROGRESS_STALE_AFTER_DAYS`** | integer | Days added to activity (or sync time when no activity) to compute **`stale_after_at`**. |
| **`PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS`** | integer | Minimum hours between **stale reminder emails** for the same snapshot after the first send. |

Other partner-related tuning may remain **env-only** (e.g. `PARTNER_PROGRESS_SEND_STALE_REMINDERS`, `PARTNER_PROGRESS_HISTORY_MIN_GAP_HOURS`)—see `config/services.php`.

---

### 15.9 `users` (columns referenced)

| Column | Purpose |
|--------|---------|
| `id` | PK; fallback external id for Startocode when `student_id` empty |
| `userId` | OMCP learner string id; default for many partners |
| `student_id` | Preferred for Startocode when set |
| `registered_course` | Used with mappings for eligibility and which snapshot row applies |

---

## 16. Staged changes on this branch — purpose of each file

This section documents **why each path that is staged for the student / partner progress work exists**. Paths are grouped so the handful of **Backpack Pro** vendor publishes (many similar Blade files) are explained once, then listed for traceability.

### 16.1 Console commands (`app/Console/Commands/`)

| File | Purpose |
|------|---------|
| `CleanupPartnerProgressWithoutProgrammeProvider.php` | Maintenance: **`partner:cleanup-progress-without-programme-provider`** — deletes `student_partner_progress` (and cascading history/rollups) for courses that have **no programme** or an **empty `programme.provider`**; supports `--dry-run` / chunked deletes. |
| `MonitorPartnerSyncHealth.php` | Ops: scans `StudentPartnerProgress` + mappings for failure-rate spikes and SLA freshness; logs alerts; optional `--json` / `--fail-on-alert` for monitoring. |
| `PartnerPreflightCommand.php` | Onboarding: validates `PartnerIntegration` rows, mappings, and resolvable drivers via `PartnerIntegrityService` before relying on sync in an environment. |
| `PartnerProbeHttpCommand.php` | Debug: **one real HTTP** single-progress GET against a partner (optional in-memory `ps_` secret); **read-only** for DB; gated to local/testing unless env allows. |
| `PrunePartnerHistory.php` | Retention: rolls up cold `student_partner_progress_history` into `student_partner_progress_history_rollups`, then deletes old raw rows in batches (scheduled daily). |
| `SendStalePartnerProgressReminders.php` | Product: sends stale-progress reminder emails when snapshots are past `stale_after_at` and cooldown rules pass. |
| `SyncPartnerProgramProgress.php` | Ingestion entrypoint: queues `SyncProgramProgressPageJob` for bulk/program pagination sync (scheduled hourly per registered driver). |
| `ValidateStudentIdIntegrity.php` | Data quality: validates admitted students’ `student_id` format, uniqueness, batch alignment (indirectly protects correct learner keys for partner APIs). |

### 16.2 Scheduler (`app/Console/Kernel.php`)

Registers hourly stale reminders, hourly `partner:sync-program-progress` per partner (incremental `updated-since`), `partner:monitor-sync-health` every 15 minutes, daily `partner:prune-history`, and other existing tasks. **Student progress depends on these schedules + a working queue worker.**

### 16.3 HTTP — Admin (`app/Http/Controllers/Admin/`)

| File | Purpose |
|------|---------|
| `AppConfigCrudController.php` | Exposes App config keys used for partner progress (stale days, reminder cooldown, Startocode partner code override, etc.). |
| `ManageStudentCrudController.php` | Student detail: partner progress preview, refresh triggers, chart payload for admin Blade. |
| `PartnerIntegrationCrudController.php` | CRUD for `partner_integrations` (Backpack): base URL, auth, endpoints, signing, response mapping — **source of truth** for outbound partner HTTP. |

### 16.4 HTTP — Student & shared (`app/Http/`)

| File | Purpose |
|------|---------|
| `StudentOperation.php` | Student-facing routes including Inertia **Learning Progress** page: loads eligibility, snapshot, history, activities for `Progress.vue`. |
| `Middleware/HandleInertiaRequests.php` | Shares global props (e.g. whether to show the Progress menu entry from `User` / config). |
| `Requests/AppConfigRequest.php` | Validates App config submissions (including partner progress keys). |
| `Requests/PartnerIntegrationRequest.php` | Validates Partner integration create/update payloads (JSON fields, URLs, partner_code). |

### 16.5 Jobs (`app/Jobs/`)

| File | Purpose |
|------|---------|
| `RefreshPartnerProgressJob.php` | Queued single-learner sync (`PartnerProgressSyncService::syncUser`) after preview/refresh — updates snapshot and may append history. |
| `SyncProgramProgressPageJob.php` | Queued bulk page fetch + per-row `syncBulkItem`; re-dispatches while partner reports more pages. |

### 16.6 Models (`app/Models/`)

| File | Purpose |
|------|---------|
| `PartnerCourseMapping.php` | Course ↔ partner link (`learning_path_id`, patterns); eligibility and `pickProgressUnit` context. |
| `PartnerIntegration.php` | Eloquent model for partner API configuration row; casts JSON columns. |
| `StudentPartnerProgressHistory.php` | History rows + helpers (e.g. combined series for charts, optional rollup merge). |
| `StudentPartnerProgressHistoryRollup.php` | Daily (or other) aggregates after pruning cold raw history. |
| `User.php` | `partnerProgressExternalIdentifier()`, menu flags (`hasPartnerProgressMenu`), registered course — all used for sync and UI gates. |

### 16.7 Observers & providers

| File | Purpose |
|------|---------|
| `Observers/PartnerIntegrationObserver.php` | When an integration is saved enabled, can kick off initial bulk sync (first page) so new partners start ingesting without a manual command. |
| `Providers/AppConfigServiceProvider.php` | Loads cached App config into Laravel config (stale days, cooldown, etc.). |
| `Providers/AppServiceProvider.php` | Binds `PartnerRegistry`, registers drivers, shared wiring. |
| `Providers/EventServiceProvider.php` | Standard Laravel event/listener map (staged when touched alongside other providers on this branch). |

### 16.8 Services (`app/Services/`)

| File | Purpose |
|------|---------|
| `PartnerCourseEligibilityService.php` | Decides if a learner+course is eligible for partner progress (mapping + enabled integration + programme provider). |
| `PartnerProgressStalenessService.php` | Computes whether reminders should send (stale + cooldown + email). |
| `PartnerProgressSyncService.php` | Core sync: resolve driver, fetch, normalize, validate contract, persist snapshot, append history, record failures. |
| `Partners/Contracts/PartnerProgressDriver.php` | Interface for `fetchStudentProgress` / bulk / normalization hooks. |
| `Partners/Generic/GenericProgressDriver.php` | DB-driven driver using `response_mapping_json` + integration row (near–no-code partners). |
| `Partners/Generic/GenericProgressDriverFactory.php` | Builds generic driver instances from registry + integration. |
| `Partners/Generic/ProgressMappingNormalizer.php` | Maps arbitrary partner JSON into internal units/metrics using mapping config. |
| `Partners/PartnerIntegrationRequestSigner.php` | HMAC / signing helpers for outbound requests. |
| `Partners/PartnerIntegrityService.php` | Preflight checks: integration row completeness, mapping presence, driver resolution. |
| `Partners/PartnerProgressPayloadValidator.php` | Optional `validation_contract_json` enforcement after normalize. |
| `Partners/PartnerRegistry.php` | Maps `partner_code` → concrete driver class (Startocode, generic, …). |
| `Partners/Startocode/PartnerProgressClient.php` | Low-level HTTP: resolve integration, build path/query, headers, retries, probe override for `partner:probe-http`. |
| `Partners/Startocode/StartocodeProgressDriver.php` | Startocode-specific normalization + single/bulk behaviour. |

### 16.9 Support (`app/Support/`)

| File | Purpose |
|------|---------|
| `PartnerCodeNormalizer.php` | Consistent slug casing/format for `partner_code` across DB and CLI. |
| `PartnerProgramSettings.php` | Program slug per partner, bulk pagination defaults (from config/env). |
| `StartocodePartnerCode.php` | Central constant/helper so `startocode` is not magic-string scattered everywhere. |

### 16.10 Config

| File | Purpose |
|------|---------|
| `config/services.php` | `partner_progress`, `partner_monitoring`, `partner_history_retention`, probe override flags, etc. |

### 16.11 Database migrations (staged)

| Migration (basename) | Purpose |
|------------------------|---------|
| `2025_04_04_000000_create_cache_table` | Laravel cache table (infrastructure; supports config/cache queues used by jobs). |
| `2025_04_05_201740_create_permission_tables` | Permission tables (Backpack/admin access to new CRUDs). |
| `2025_05_09_210137_create_report_views` | Reporting views (unchanged semantics; included if touched for compatibility). |
| `2025_07_16_195304_create_cache_table` | Cache table alignment / duplicate guard depending on deploy order. |
| `2026_03_31_120100_seed_startocode_partner_course_mappings` | Seeds Startocode ↔ course mappings for dev/staging parity. |
| `2026_04_02_120000_create_partner_integrations_table` | Creates `partner_integrations` (admin-configurable partner HTTP). |
| `2026_04_02_133000_add_endpoints_json_to_partner_integrations_table` | Adds `endpoints_json` for single/bulk paths and query maps. |
| `2026_04_02_141500_add_path_param_bindings_json_to_partner_integrations_table` | Path placeholder bindings (`{omcp_id}`, etc.). |
| `2026_04_02_151500_add_coalesced_unique_to_student_partner_progress` | **MySQL** generated `course_id_coalesced` + unique `(partner_code, omcp_id, course_id_coalesced)` for stable upserts. |
| `2026_04_02_160000_create_student_partner_progress_history_rollups_table` | Rollup table for pruned history. |
| `2026_04_06_100000_add_partner_progress_contract_json_columns` | Optional validation contract + related JSON on integrations/snapshots as designed. |
| `2026_04_06_200000_add_partner_progress_stale_after_days_app_config` | Seeds or wires App config key for stale days (admin-tunable). |
| `2026_04_06_210000_add_refresh_timestamp_header_without_signing_to_partner_integrations` | Column for partners needing timestamp refresh without signing. |
| `2026_04_08_120000_update_stale_partner_progress_email_template_dynamic_breakdown` | Mail template update for dynamic metric breakdown in reminders. |
| `2026_04_10_000000_add_partner_progress_reminder_cooldown_hours_app_config` | App config for reminder cooldown hours. |
| `2026_04_12_000000_add_partner_progress_startocode_partner_code_app_config` | App config override for Startocode partner code if needed. |

### 16.12 Seeders (`database/seeders/`)

| File | Purpose |
|------|---------|
| `AppConfigSeeder.php` | Seeds default App config entries (including partner progress keys where applicable). |

### 16.13 Front controller & student UI

| File | Purpose |
|------|---------|
| `public/index.php` | Standard Laravel front controller (may be touched for trust proxies or server baseline with this branch). |
| `resources/js/Pages/Student/Progress.vue` | **Student Learning Progress** page: Chart.js histogram + line trends, range filters, indicator chips, responsive layout. |

### 16.14 Admin Blade (partner-specific)

| File | Purpose |
|------|---------|
| `resources/views/admin/partner_integration/partner_code_toggle_script.blade.php` | JS for Partner Code dropdown vs manual entry on integration form. |
| `resources/views/vendor/backpack/crud/manage_student_show.blade.php` | Admin student show: partner progress charts and actions (extends/customizes vendor view). |

### 16.15 Backpack Pro vendor views (`resources/views/vendor/backpack/pro/**`)

**Purpose (all files in this tree together):** Laravel Backpack **Pro** publishes Blade partials for **buttons** (clone, trash, bulk actions), **columns** (relationship, repeatable, select2, …), **fields** (repeatable, relationship, JSON-heavy editors), and **filters**. This project vendors them under `resources/views/vendor/backpack/pro/` so Backpack Pro field types and list operations work **without missing view errors**. They are **not** business rules for student progress; they are **UI scaffolding** required for admin CRUDs—especially **Partner integrations** and any Pro fields on related screens.

**Staged paths (for traceability):**  
`buttons/bulk_clone.blade.php`, `bulk_delete.blade.php`, `bulk_destroy.blade.php`, `bulk_restore.blade.php`, `bulk_trash.blade.php`, `clone.blade.php`, `custom_view.blade.php`, `destroy.blade.php`, `restore.blade.php`, `trash.blade.php`;  
`columns/` — `address_google`, `array`, `array_count`, `base64_image`, `browse`, `browse_multiple`, `code_mirror_editor`, `date_picker`, `date_range`, `datetime_picker`, `dropzone`, `easymde`, `icon_picker`, `image`, `markdown`, `relationship`, `relationship_count`, `repeatable`, `select2`, `select2_from_ajax`, `select2_from_ajax_multiple`, `select2_from_array`, `select2_grouped`, `select2_multiple`, `select2_nested`, `select_and_order`, `slug`, `table`, `video`;  
`fields/` — `address`, `address_google`, `base64_image`, `code_mirror_editor`, `date_picker`, `date_range`, `datetime_picker`, `dropzone`, `easymde`, `google_map`, `icon_picker`, `image`, `phone`, `relationship` (+ nested `relationship/*`), `repeatable`, `select2`, `select2_from_ajax`, `select2_from_ajax_multiple`, `select2_from_array`, `select2_grouped`, `select2_json_from_api`, `select2_multiple`, `select2_nested`, `select_and_order`, `slug`, `table`, `video`, `inc/repeatable_row.blade.php`;  
`filters/` — `date`, `date_range`, `dropdown`, `range`, `select2`, `select2_ajax`, `select2_multiple`, `simple`, `text`, `trashed`, `view`.

### 16.16 Routes & tests

| File | Purpose |
|------|---------|
| `routes/backpack/custom.php` | Registers Backpack admin routes (Partner integration CRUD, student progress admin actions, menu entries). |
| `tests/Unit/PartnerProgressStalenessServiceTest.php` | Unit tests for reminder / staleness logic. |

---

## 17. Partner integration records already in the database

**You do not need new code** to “support” integrations that are **already stored** in `partner_integrations` (and related `partner_course_mappings`), as long as:

- `is_enabled` is **true** and `base_url` / `endpoints_json` / auth / signing are correct for that environment.
- **`partner_code`** matches the programme **`provider`** and the **course mapping** rows for the learner’s registered course.
- A **driver** is registered for that `partner_code` (`PartnerRegistry`) or the **generic** driver applies via `response_mapping_json`.

The runtime path is: **DB row → `PartnerIntegration` model → `PartnerProgressClient` / driver → sync jobs → `student_partner_progress` + history → student/admin UIs.**

Use **`php artisan partner:preflight {partner_code}`** after deploy or config edits to confirm the row + mappings + driver resolve. Production data stays authoritative; staging can copy or mimic the same shape.

---

## 18. How to test this branch

1. **Install & migrate**  
   - `composer install`  
   - `php artisan migrate` (review any MySQL-specific migrations in non-MySQL environments).

2. **Config / queue**  
   - Copy `.env` from `.env.example`; set `QUEUE_CONNECTION` and run **`php artisan queue:work`** (or Horizon) so `RefreshPartnerProgressJob` and `SyncProgramProgressPageJob` run.  
   - Ensure **scheduler** runs: cron `* * * * * php artisan schedule:run` (or platform scheduler).

3. **Automated tests**  
   - `php artisan test` (or `./vendor/bin/phpunit`) — includes `PartnerProgressStalenessServiceTest` and any feature tests for the Progress page / client added on the branch.

4. **Manual — admin**  
   - Log into Backpack → **Partner integrations**: confirm rows load, edit-save works (Pro fields), **Preflight** via CLI as above.  
   - **Manage student**: open a learner with a mapped course — partner progress block, refresh, charts.

5. **Manual — student**  
   - Log in as a student who is **eligible** (menu entry “Progress” when `hasPartnerProgressMenu` / config allows).  
   - Open **Learning Progress**: verify snapshot %, last synced, charts when **≥ 2** history points exist.

6. **Ops commands (non-prod or controlled)**  
   - `php artisan partner:monitor-sync-health [--json]`  
   - `php artisan partner:prune-history --dry-run` before first real prune in an environment.

7. **HTTP probe (local/staging)**  
   - See `docs/partner-progress-runbook.md`: `partner:probe-http` is restricted by environment; use for signing/path debugging.

---

## 19. Tips for teammates (Startocode API + full charts)

For a **quick sanity check** of the **Startocode** single-progress API from this app’s perspective, other team members can use a known OMCP learner id **`001-26-00015`** (`users.userId`) when driving probes or when checking partner payloads — it’s a handy reference id that matches how we key **`{omcp_id}`** in paths.

To get the **full “student progress visualizations” feel** (histogram, trend lines, indicator chips), combine that with **seeded or migrated data** that includes:

- a **snapshot** on `student_partner_progress`, and  
- **multiple** `student_partner_progress_history` points (or run syncs over time / use seeders like comprehensive progress seeders on a **non-production** database).

Charts intentionally need **at least two** sync/history points to render; one point alone will show copy explaining that more sync points are needed.

---

## 20. Related docs

- `docs/partner-progress-runbook.md` — operations, signing, cron, mail template  
- `docs/partner-progress-implementation-guide.md` — implementation notes (if present)  
- `docs/qa/QA_README.md` — QA checklist / seed templates (if present in repo)

---

*Keep this document aligned with `database/migrations` and `config/services.php` when behaviour changes.*
