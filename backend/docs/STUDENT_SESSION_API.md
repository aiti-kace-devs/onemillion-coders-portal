# Student session API (Vue portal) — specification, examples, testing

This document defines the **v1 JSON API** for course session selection aligned with `course_sessions.limit`, **`programme_quotas`**, optional **`centre_time_blocks`** / bookings, and intelligent **alternatives** (same centre / other courses; same programme+batch at other centres). Use it for Vue integration, QA, and backend implementation.

**Base URL:** `{APP_URL}/api/v1`  
**Auth:** `Authorization: Bearer {token}` (Laravel Sanctum personal access token or SPA cookie flow per your setup).

**Flow split (see `stud_session_api.md`):** `data.flow` is **`centre_support` when `users.support` is true** (online learners attending at a centre, or in-person). **`simple` when `support` is false** (fully remote online has `data.attendance_mode: fully_remote`). Quota resolution: remote online uses **nationwide** `programme_quotas`; online+support with a `centre_id` uses **per-centre** quota like in-person.

---

## Common conventions

| Item | Rule |
|------|------|
| Content-Type | `application/json` for POST bodies |
| Success | HTTP **200** with `success: true` |
| Validation | HTTP **422** with Laravel `message` + `errors` |
| Auth failure | HTTP **401** |
| Business rule / conflict | HTTP **409** with `success: false` + `error.code` |
| Rate limit | HTTP **429** (configure `throttle` middleware) |

All timestamps are **ISO 8601** strings in the app timezone unless you standardize to UTC in resources.

---

## 1. `GET /api/v1/student/session-options`

Resolves availability for the **authenticated student’s** admission, optional explicit picks, and **alternatives** when the preferred session at the preferred centre is full.

### Query parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `preferred_centre_id` | No | Centre the student prefers (defaults to `courses.centre_id` for the student’s admitted course). Used for ranking “other centres” and filtering “same centre” alternatives. |
| `course_id` | No | Defaults to `user_admission.course_id`. If provided, must match the admission’s course or be rejected (409/422). |
| `course_session_id` | No | If set, evaluates this **exact** `course_sessions.id` against the student’s course (must belong to that course) for `requested` block. |
| `programme_id` | No | Redundant if inferred from admission; if sent, must match admitted course’s programme (422 if mismatch). |
| `batch_id` | No | Same as above for `admission_batches.id`. |

**Note:** For **online** programmes (`programmes.mode_of_delivery = online`), geographic alternatives may be empty; session pooling still follows `CourseSession::sharedSessionIds()` behaviour.

### Example request

```http
GET /api/v1/student/session-options?preferred_centre_id=12&course_session_id=440 HTTP/1.1
Host: api.example.com
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz
Accept: application/json
```

### Success response — HTTP 200

**Case A — requested slot available**

```json
{
  "success": true,
  "data": {
    "requested": {
      "available": true,
      "reason": null,
      "course_id": 101,
      "course_name": "Web Dev — Accra Hub",
      "programme_id": 5,
      "programme_title": "Full Stack Web",
      "batch_id": 3,
      "preferred_centre_id": 12,
      "preferred_centre_title": "Accra Learning Centre",
      "course_session_id": 440,
      "session_label": "Morning",
      "slots_left": 14,
      "limits": {
        "session_cap": 30,
        "programme_quota": { "applies": true, "max": 500, "used": 120, "remaining": 380 },
        "block_required": false
      }
    },
    "alternatives": {
      "same_centre_other_courses": [],
      "same_course_other_centres": []
    },
    "meta": {
      "generated_at": "2026-04-11T10:15:30+00:00",
      "cache_ttl_seconds": 15,
      "admission_id": 9001
    }
  }
}
```

**Case B — requested slot unavailable (session full), alternatives populated**

```json
{
  "success": true,
  "data": {
    "requested": {
      "available": false,
      "reason": "session_full",
      "course_id": 101,
      "course_session_id": 440,
      "slots_left": 0,
      "limits": {
        "session_cap": 30,
        "programme_quota": { "applies": true, "max": 500, "used": 120, "remaining": 380 }
      }
    },
    "alternatives": {
      "same_centre_other_courses": [
        {
          "course_id": 105,
          "course_name": "Data Fundamentals — Accra Hub",
          "programme_id": 7,
          "sessions": [
            {
              "course_session_id": 501,
              "session_label": "Afternoon",
              "slots_left": 8,
              "course_time": "14:00",
              "status": true
            }
          ]
        }
      ],
      "same_course_other_centres": [
        {
          "centre_id": 18,
          "centre_title": "Tema Learning Centre",
          "branch_id": 2,
          "branch_title": "Greater Accra",
          "geo_tier": "same_constituency",
          "distance_km": null,
          "course_id": 102,
          "sessions": [
            {
              "course_session_id": 441,
              "session_label": "Morning",
              "slots_left": 3,
              "course_time": "09:00"
            }
          ]
        }
      ]
    },
    "meta": {
      "generated_at": "2026-04-11T10:15:30+00:00",
      "cache_ttl_seconds": 15,
      "admission_id": 9001
    }
  }
}
```

**`requested.reason` (machine codes)** — use consistently in Vue:

| Code | Meaning |
|------|---------|
| `null` | Available (see `requested.available: true`) |
| `session_full` | `CourseSession::slotLeft() < 1` |
| `programme_quota_full` | Confirmed admissions against applicable `programme_quotas` row ≥ `max_enrollments` |
| `block_full` | Centre time block capacity exhausted (when block feature enforced) |
| `block_required` | Student must confirm a centre block before session (when configured) |
| `not_eligible` | Admission/course state does not allow selection |

**`geo_tier` (ranking)** — lower sort order = better:

1. `same_constituency`
2. `shared_district` (via `district_centre` pivot)
3. `same_branch` (region)
4. `other` (then `distance_km` if GPS present)

### Failure responses

**401 — Unauthenticated**

```json
{
  "message": "Unauthenticated."
}
```

**422 — Validation (bad query)**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "preferred_centre_id": ["The selected preferred centre id is invalid."]
  }
}
```

**404 — No admission**

```json
{
  "success": false,
  "error": {
    "code": "no_admission",
    "message": "No course admission found for this account."
  }
}
```

**409 — Conflict (e.g. programme_id mismatch)**

```json
{
  "success": false,
  "error": {
    "code": "programme_mismatch",
    "message": "Programme does not match your admitted course."
  }
}
```

---

## 2. `POST /api/v1/student/session-confirm`

Confirms **`course_session_id`** for the authenticated student: same transactional rules as web `StudentOperation::confirm_session` (slot check, programme quota, optional block gate, `user_admission.session` + `confirmed`).

### Headers

| Header | Required | Description |
|--------|----------|---------------|
| `Authorization` | Yes | Bearer token |
| `Idempotency-Key` | Recommended | Unique string per user action; duplicate POST with same key returns same success payload without double booking |

### JSON body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `session_id` | integer | Yes | `course_sessions.id` (same semantic as web `session_id`) |

### Example request

```http
POST /api/v1/student/session-confirm HTTP/1.1
Host: api.example.com
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz
Content-Type: application/json
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000

{
  "session_id": 440
}
```

### Success — HTTP 200

```json
{
  "success": true,
  "data": {
    "admission_id": 9001,
    "course_session_id": 440,
    "session_name": "Web Dev — Accra Hub - Morning Session",
    "confirmed_at": "2026-04-11T10:20:00+00:00",
    "changed_session": false
  }
}
```

`changed_session: true` when admission was already confirmed and session was updated (only if `ALLOW_SESSION_CHANGE` / config permits).

### Failure examples

**422 — Validation**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "session_id": ["The selected session id is invalid."]
  }
}
```

**409 — No slots**

```json
{
  "success": false,
  "error": {
    "code": "session_full",
    "message": "No slots available for this session."
  }
}
```

**409 — Programme quota**

```json
{
  "success": false,
  "error": {
    "code": "programme_quota_full",
    "message": "This programme has reached its enrolment limit for your selection."
  }
}
```

**409 — Session change disabled**

```json
{
  "success": false,
  "error": {
    "code": "session_change_disabled",
    "message": "Unable to change session at this time. Contact administrator."
  }
}
```

**409 — Session not for your course**

```json
{
  "success": false,
  "error": {
    "code": "invalid_session",
    "message": "That session is not available for your course."
  }
}
```

**409 — Idempotency replay (optional pattern)**

Same HTTP 200 body as first success when `Idempotency-Key` matches a completed confirm.

---

## 3. Testing

### Prerequisites

- Migrated DB including `programme_quotas`, `centre_time_blocks`, `student_centre_bookings`, `booking_waitlist`.
- A user with `user_admission` row and valid `course_id`.
- Sanctum token (Personal Access Token or SPA).

### Manual — obtain token (tinker)

```bash
cd backend
php artisan tinker
>>> $u = \App\Models\User::where('email', 'student@example.com')->first();
>>> $u->createToken('test')->plainTextToken;
```

### cURL — session-options

```bash
curl -sS -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "https://your-app.test/api/v1/student/session-options?preferred_centre_id=1"
```

### cURL — session-confirm

```bash
curl -sS -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{"session_id":440}' \
  "https://your-app.test/api/v1/student/session-confirm"
```

### PHPUnit (feature test sketch)

- `actingAs($user, 'sanctum')`
- `getJson('/api/v1/student/session-options')` → assert 200 + `success`
- `postJson('/api/v1/student/session-confirm', ['session_id' => $id])` → assert 200 + `data.confirmed_at`
- Seed `course_sessions.limit = 1`, two users confirm → second gets 409 `session_full`
- With `programme_quotas.max_enrollments = N` and N confirmed admissions → next confirm 409 `programme_quota_full`

### Load / concurrency (optional)

- k6 or Artillery against `GET session-options` with ramping VUs; watch DB CPU and p95 latency.
- Two parallel `POST session-confirm` for last slot: one must succeed, one `session_full` (transaction + `lockForUpdate` on confirm path).

### Regression checklist

- [ ] Web `StudentOperation::confirm_session` and API use **shared** validation path (no drift).
- [ ] Online programme sibling session pooling matches `CourseSession::slotLeft()`.
- [ ] Alternatives empty for online if no geographic centres apply.
- [ ] `preferred_centre_id` invalid → 422.
- [ ] Unauthenticated → 401.

---

## 4. Implementation mapping (backend)

When implementing in Agent mode:

1. Migration: `2026_04_11_120000_create_scheduling_tables.php` (tables above).
2. Models: `ProgrammeQuota`, `CentreTimeBlock`, `StudentCentreBooking`, `BookingWaitlist`; `Programme::quotas()`.
3. Services: `ProgrammeQuotaService`, `SessionAlternativesService`, `ConfirmStudentSessionService` (used by API + `StudentOperation`).
4. Routes: `routes/api.php` prefix `v1/student`, middleware `auth:sanctum` + `throttle:api`.
5. Config: `config/scheduling.php` keys e.g. `require_centre_block_for_confirm` (default `false`).

---

## 5. Changelog

| Date | Change |
|------|--------|
| 2026-04-11 | Initial API spec + testing guide |
