# Changelog: Programme Batches, Intelligent Quota & Booking

## Migrations (4 new)

- **`2026_04_13_000001_add_duration_fields_to_programmes_table`** — Adds `duration_hours`, `duration_in_days`, and `time_allocation` columns to `programmes`
- **`2026_04_13_000002_add_capacity_fields_to_centres_table`** — Adds `seat_count`, `short_slots_per_day`, and `long_slots_per_day` columns to `centres`
- **`2026_04_13_000003_create_programme_batches_table`** — Creates `programme_batches` table with foreign keys to `admission_batches`, `programmes`, and `centres`; unique constraint on `(admission_batch_id, programme_id, centre_id, start_date)`; composite and date-range indexes for query performance; soft deletes
- **`2026_04_13_000004_add_programme_batch_id_to_user_admission_table`** — Adds `programme_batch_id` foreign key to `user_admission`

## Models

- **`Programme`** — Added `duration_hours`, `duration_in_days`, `time_allocation` to fillable; `saving` hook auto-computes `duration_in_days` and `time_allocation` from `duration_hours` using tier table (2h/day for <40h, 4h/day otherwise with fixed day counts per tier)
- **`Centre`** — Added `seat_count`, `short_slots_per_day`, `long_slots_per_day` to fillable; `saving` hook auto-derives slot counts from `seat_count` using AppConfig percentages when not explicitly set, with rounding adjustment so short + long = seat_count
- **`ProgrammeBatch`** (new) — Full model with soft deletes, relationships to Batch/Programme/Centre/UserAdmission
- **`UserAdmission`** — Added `programme_batch_id` to fillable; added `programmeBatch()` relationship
- **`Batch`** — Added `programmeBatches()` relationship
- **`CourseBatch`** — Converted to thin deprecated alias extending `ProgrammeBatch` for legacy compatibility

## Services (3 new)

- **`ProgrammeBatchGenerator`** — Generates continuous, non-overlapping weekly batches (Mon–Fri, skips weekends) within an admission batch for each (programme × centre) pair; idempotent via unique key check; fires `ProgrammeBatchCreated` event only for genuinely new batches; uses `SchoolDayCalculator` helper
- **`AvailabilityService`** — Single `getAvailableSlots()` entry point with 5-minute cache; dynamic reallocation logic (short courses inherit full centre capacity when no long course fits remaining window); ordered recommendations (same-branch centre → alternative course → no-centre support → waitlist); `clearCache()` for explicit invalidation
- **`BookingService`** — Atomic `book()` with row-level lock; prevents duplicate booking into same batch; auto-cancels previous batch slot when reassigning a user to a different batch; `cancel()` with 7-day-before-end cutoff for slot restoration; fires `AdmissionSlotFreed` event; invalidates availability cache on both book and cancel

## Jobs, Events, Listeners

- **`GenerateProgrammeBatchesJob`** (new) — Queue job dispatched by observers; delegates to `ProgrammeBatchGenerator`
- **`ProgrammeBatchCreated`** (new) — Event carrying admission batch and collection of newly created programme batches
- **`AdmissionSlotFreed`** (new) — Event carrying programme batch and optional admission record
- **`NotifyWaitlistedUsers`** (new) — Listens to both events; caps notifications to `WAITLIST_NOTIFY_LIMIT` (default 5) ordered by `created_at`; batch-loads users via `whereIn` to avoid N+1; guards against missing `admission_waitlist` table
- **`WaitlistSlotAvailableNotification`** (new) — Queued mail notification with eager-loaded relationships to prevent lazy-loading errors

## Observers (2 new)

- **`BatchObserver`** — Dispatches `GenerateProgrammeBatchesJob` only when `start_date`, `end_date`, `status`, `centre_ids`, or `programme_ids` change; cascades delete to programme batches
- **`CentreObserver`** — Dispatches regeneration only when `seat_count`, `short_slots_per_day`, or `long_slots_per_day` change; uses integer cast for JSON `centre_ids` matching

## Helpers (1 new)

- **`SchoolDayCalculator`** — Shared static `count()` and `add()` methods for Mon–Fri date calculations; eliminates duplication between Generator and AvailabilityService

## Admin UI (Backpack CRUD)

- **`ProgrammeBatchCrudController`** (new) — Full CRUD with filters by admission batch, programme, and centre; displays dates, enrolments, available slots, and status
- **`BatchCrudController`** — Added "Regenerate Programme Batches" button in Assign Courses tab; added `regenerate()` method
- **`CentreCrudController`** — Added `seat_count`, `short_slots_per_day`, `long_slots_per_day` fields to General tab
- **`ProgrammeFieldHelpers`** — Added `duration_hours` (editable), `duration_in_days` (readonly), and `time_allocation` (readonly) fields to Info tab

## Validation

- **`CentreRequest`** — Added validation for capacity fields; `withValidator` hook enforces `short_slots_per_day + long_slots_per_day = seat_count` when all three are provided
- **`ProgrammeBatchRequest`** (new) — Validates all required fields for programme batch CRUD

## Routes

- **API** — `GET /api/availability` → `AvailabilityController@index`
- **Backpack** — `POST /batch/{id}/regenerate-batches` → `BatchCrudController@regenerate`; CRUD + regenerate route for `/programme-batch`

## Event Service Provider

- Registered `NotifyWaitlistedUsers` listener for `AdmissionSlotFreed` and `ProgrammeBatchCreated` events
- Registered `BatchObserver` and `CentreObserver`

## Tests (2 new)

- **`ProgrammeBatchGeneratorTest`** — Tests continuous non-overlapping batch generation, 2-week programme batches, and idempotency
- **`BookingServiceTest`** — Tests successful booking, rejection when no slots, cancellation with slot restore, and sequential quota enforcement

## AppConfig Keys (to be created via admin UI)

| Key | Default | Purpose |
|-----|---------|---------|
| `SHORT_SLOTS_PERCENTAGE` | 60 | Percentage of seat_count for short-course slots |
| `LONG_SLOTS_PERCENTAGE` | 40 | Percentage of seat_count for long-course slots |
| `WAITLIST_NOTIFY_LIMIT` | 5 | Max waitlisted users notified per slot free |
| `AVAILABILITY_CACHE_TTL` | 300 | Cache TTL in seconds for availability queries |
