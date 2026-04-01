# Partner Progress Runbook (Startocode)

This runbook is only for the student partner progress feature:
- Fetching and storing partner progress
- Rendering progress in admin student preview
- Sending stale-progress reminder emails

## 1) What this feature needs

- Student must exist in `users`
- Student must have an `omcp_id` source (`users.userId` is used)
- Student course must be mapped to Startocode in `partner_course_mappings`
- Startocode API credentials must be configured in env
- Queue worker and scheduler must be running in production

## 2) Environment variables (production)

Set these in `backend/.env` on production:

```env
STARTOCODE_PARTNER_CODE=startocode
STARTOCODE_BASE_URL=https://<partner-host>
STARTOCODE_API_TOKEN=<partner-api-token>
STARTOCODE_TIMEOUT_SECONDS=10
STARTOCODE_STALE_AFTER_DAYS=7
STARTOCODE_PREVIEW_REFRESH_MINUTES=30
STARTOCODE_SEND_STALE_REMINDERS=true
STARTOCODE_REMINDER_COOLDOWN_HOURS=24
STARTOCODE_REMINDER_BATCH_SIZE=200
```

After changing env values:

```bash
php artisan config:clear
php artisan config:cache
```

## 3) Database setup

Run migrations:

```bash
php artisan migrate --force
```

These objects are required:
- `student_partner_progress`
- `partner_course_mappings`
- reminder email template row `PARTNER_PROGRESS_STALE_REMINDER_EMAIL`

## 4) Partner-course mapping setup

Table: `partner_course_mappings`

Required fields:
- `partner_code` must match `config('services.partner_startocode.code')` (default `startocode`, override with `STARTOCODE_PARTNER_CODE`)
- `is_active` = `1`
- `course_id` (preferred exact mapping), or
- `course_name_pattern` (fallback pattern matching)
- `learning_path_id` (recommended to set)

Recommendation:
- Use exact `course_id` mappings in production for stability.
- Keep pattern-based matching only as fallback.

## 5) Queue and scheduler setup

This feature uses background execution for refresh/reminders.

### Queue worker

Run queue workers in production (Supervisor/Horizon):

```bash
php artisan queue:work --sleep=1 --tries=3
```

### Scheduler

Ensure cron calls Laravel scheduler every minute:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

The stale reminder command is scheduled in code:
- `partner:send-stale-progress-reminders` (hourly)

## 6) Manual test flow for one student

1. Open admin preview page:
   - `/admin/manage-student/{user_id}/show`
2. Click **Refresh Progress** in the `Partner Learning Progress` card.
3. Verify row in `student_partner_progress` updates:
   - `last_synced_at`
   - `progress_summary_json`
   - `progress_raw_json`
   - `overall_progress_percent`
4. Confirm card renders:
   - Overall completion
   - Video/Quiz/Project/Task bars
   - Last activity/sync
   - Fresh/Stale badge

## 7) Stale reminder email template

Template key used by this feature:
- `PARTNER_PROGRESS_STALE_REMINDER_EMAIL`

### How to edit the message

Use Backpack admin:
- Go to `Admin -> Email Template`
- Open template `PARTNER_PROGRESS_STALE_REMINDER_EMAIL`
- Edit `content` and save

Supported placeholders for this reminder:
- `{name}`
- `{course_name}`
- `{overall_progress}`
- `{video_progress}`
- `{quiz_progress}`
- `{project_progress}`
- `{task_progress}`
- `{last_activity_at}`

Sample content:

```md
## Hello {name},

This is a friendly reminder to continue your progress in **{course_name}**.

[component]: # ('mail::panel')
**Current progress:** {overall_progress}  <br>
Video: {video_progress}%  <br>
Quiz: {quiz_progress}%  <br>
Project: {project_progress}%  <br>
Task: {task_progress}%  <br>
Last activity: {last_activity_at}
[endcomponent]: #
```

## 8) Operations and troubleshooting

- If preview shows `Syncing...` indefinitely:
  - Check queue worker is running
  - Check `last_sync_error` in `student_partner_progress`
- If `last_sync_error` says credentials not configured:
  - Verify `STARTOCODE_BASE_URL` and `STARTOCODE_API_TOKEN`
  - Rebuild config cache
- If reminders are not sent:
  - Verify scheduler cron
  - Verify `STARTOCODE_SEND_STALE_REMINDERS=true`
  - Verify template exists with correct key
  - Check `last_reminder_sent_at` and `reminder_count`

## 9) Security notes

- Never log or expose partner token
- Keep `.env` restricted
- Do not store sensitive secrets in DB templates or logs
- Prefer masked identifiers in logs for student references
