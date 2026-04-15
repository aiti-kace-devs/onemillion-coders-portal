<?php

namespace App\Helpers;

use App\Models\Centre;
use App\Models\CentreSession;
use App\Models\CourseSession;
use App\Models\MasterSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CentreSessionHelper
{
    public static function ensureAccess(Centre $centre): void
    {
        $admin = backpack_user();

        if (! $admin || (! $admin->can('centre.read.all') && ! $admin->can('centre.read.self'))) {
            abort(403, 'Unauthorized action.');
        }

        $visibleCentreIds = CentreVisibilityHelper::currentAdminVisibleCentreIds();
        if (is_array($visibleCentreIds) && ! in_array($centre->id, $visibleCentreIds, true)) {
            abort(403, 'You are not allowed to access this centre.');
        }
    }

    public static function getSessionsCollection(Centre $centre): Collection
    {
        // Fetch all active master sessions as the global baseline.
        $masterSessions = MasterSession::active()->orderBy('id')->get();

        // Fetch this centre's overrides, keyed by master_session_id for O(1) lookup.
        $centreOverrides = CentreSession::query()
            ->where('centre_id', $centre->id)
            ->whereNotNull('master_session_id')
            ->get()
            ->keyBy('master_session_id');

        // Merge: master session is the default; centre override takes precedence
        // for overridable fields (status, link, limit).
        $merged = $masterSessions->map(function ($master) use ($centreOverrides) {
            $override = $centreOverrides->get($master->id);

            return [
                'id'               => $override ? (int) $override->id : null,
                'master_session_id' => (int) $master->id,
                'session'          => (string) $master->session_type,
                'limit'            => $override ? (int) $override->limit : 100,
                'course_time'      => (string) $master->time,
                'link'             => $override?->link,
                'status'           => $override !== null
                    ? ($override->status === null ? true : (bool) $override->status)
                    : (bool) $master->status,
            ];
        });

        // Append any legacy centre-only sessions (no master_session_id) so existing
        // data is not lost during migration.
        $legacySessions = CentreSession::query()
            ->where('centre_id', $centre->id)
            ->whereNull('master_session_id')
            ->orderBy('id')
            ->get()
            ->map(function ($session) {
                return [
                    'id'               => (int) $session->id,
                    'master_session_id' => null,
                    'session'          => (string) $session->session,
                    'limit'            => (int) $session->limit,
                    'course_time'      => (string) $session->course_time,
                    'link'             => $session->link,
                    'status'           => $session->status === null ? true : (bool) $session->status,
                ];
            });

        return $merged->values()->concat($legacySessions->values());
    }

    public static function getFormPayload(?Centre $centre): string
    {
        if (! $centre instanceof Centre || ! $centre->exists) {
            return '[]';
        }

        return self::getSessionsCollection($centre)->toJson();
    }

    public static function extractRowsFromPayload(Request $request): Collection
    {
        $payload = $request->input('centre_sessions_payload');

        if ($payload === null || trim((string) $payload) === '') {
            return collect();
        }

        $decoded = json_decode((string) $payload, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'centre_sessions_payload' => 'The submitted centre sessions are invalid.',
            ]);
        }

        if (empty($decoded)) {
            return collect();
        }

        return self::validateAndNormalizeRows($decoded);
    }

    public static function validateAndNormalizeRows(array $rows): Collection
    {
        $validated = Validator::make(
            ['sessions' => $rows],
            self::validationRules()
        )->validate();

        $normalizedRows = self::normalizeRows($validated['sessions'] ?? []);

        if ($normalizedRows->isEmpty()) {
            throw ValidationException::withMessages([
                'centre_sessions_payload' => 'Add at least one valid centre session before saving.',
            ]);
        }

        $duplicateSignatures = $normalizedRows
            ->filter(fn ($row) => $row['status'])
            ->map(fn ($row) => self::rowSignature($row['session'], $row['course_time']))
            ->duplicates();

        if ($duplicateSignatures->isNotEmpty()) {
            throw ValidationException::withMessages([
                'centre_sessions_payload' => 'An active centre session with the same session type and course time can only be added once.',
            ]);
        }

        return $normalizedRows;
    }

    public static function persist(Centre $centre, Collection $rows): void
    {
        DB::transaction(function () use ($centre, $rows) {
            $existingSessions = CentreSession::query()
                ->where('centre_id', $centre->id)
                ->get();

            $existingSessionsById = $existingSessions->keyBy('id');
            $retainedSessionIds = [];

            // Cache master sessions for default comparison
            $masterSessions = MasterSession::query()
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                // --- Master-linked row: create/update a centre-level override only when values differ from master ---
                if (! empty($row['master_session_id'])) {
                    $masterSessionId = (int) $row['master_session_id'];
                    $master = $masterSessions->get($masterSessionId);

                    $existingOverride = $existingSessions
                        ->first(fn ($s) => (int) $s->master_session_id === $masterSessionId);

                    if ($existingOverride) {
                        $existingOverride->fill(self::buildSessionPayload($centre, $row));
                        $existingOverride->save();
                        $retainedSessionIds[] = (int) $existingOverride->id;
                    } elseif ($master !== null && self::rowMatchesMasterDefaults($row, $master)) {
                        // Values match master defaults — no need to create an override record.
                        // The merged view in getSessionsCollection() will show the master defaults.
                        $retainedSessionIds[] = 'master_'.$masterSessionId;
                    } else {
                        $created = CentreSession::create(self::buildSessionPayload($centre, $row));
                        $retainedSessionIds[] = (int) $created->id;
                    }

                    continue;
                }

                // --- Existing legacy session identified by its id ---
                if (! empty($row['id'])) {
                    $existingSession = $existingSessionsById->get($row['id']);

                    if (! $existingSession) {
                        throw new \RuntimeException('One or more submitted sessions could not be matched to the selected centre. Reload and try again.');
                    }

                    $existingSession->fill(self::buildSessionPayload($centre, $row));
                    $existingSession->save();
                    $retainedSessionIds[] = (int) $existingSession->id;

                    continue;
                }

                // --- New legacy row: create only for this centre (no cross-centre sync) ---
                $created = CentreSession::create(self::buildSessionPayload($centre, $row));
                $retainedSessionIds[] = (int) $created->id;
            }

            // Only mark inactive legacy sessions (master sessions are controlled by the MasterSession model status)
            $legacySessions = $existingSessions->whereNull('master_session_id');
            self::markMissingSessionsInactive($legacySessions, $retainedSessionIds);
        });
    }

    public static function syncAfterCrud(?Centre $centre, Collection $rows): void
    {
        if (! $centre instanceof Centre || $rows->isEmpty()) {
            return;
        }

        try {
            self::persist($centre, $rows);
        } catch (\RuntimeException $e) {
            \Alert::warning($e->getMessage())->flash();
        } catch (\Throwable $e) {
            report($e);
            \Alert::warning('Centre saved, but the centre sessions could not be synced.')->flash();
        }
    }

    protected static function validationRules(): array
    {
        return [
            'sessions' => 'required|array|min:1',
            'sessions.*.id' => 'nullable|integer',
            'sessions.*.master_session_id' => 'nullable|integer|exists:master_sessions,id',
            'sessions.*.session' => 'required|string|in:Morning,Afternoon,Evening,Fullday,Online',
            'sessions.*.limit' => 'required|integer|min:1|max:100000',
            'sessions.*.course_time' => 'required|string|max:255',
            'sessions.*.link' => 'nullable|string|max:255',
            'sessions.*.status' => 'nullable|boolean',
        ];
    }

    protected static function normalizeRows(array $rows): Collection
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
                    'master_session_id' => isset($row['master_session_id']) && $row['master_session_id'] !== ''
                        ? (int) $row['master_session_id']
                        : null,
                    'session' => trim((string) ($row['session'] ?? '')),
                    'limit' => (int) ($row['limit'] ?? 0),
                    'course_time' => trim((string) ($row['course_time'] ?? '')),
                    'link' => isset($row['link']) ? trim((string) $row['link']) : null,
                    'status' => isset($row['status']) ? (bool) $row['status'] : true,
                ];
            })
            ->filter(function ($row) {
                return $row['session'] !== '' && $row['course_time'] !== '';
            })
            ->values();
    }

    protected static function rowSignature(string $session, string $courseTime): string
    {
        $normalizedSession = strtolower(trim($session));
        $normalizedCourseTime = strtolower(preg_replace('/\s+/', ' ', trim($courseTime)));

        return $normalizedSession.'|'.$normalizedCourseTime;
    }

    /**
     * Check whether a submitted form row matches the master session's default values.
     * When all values match, no centre-level override is needed.
     */
    protected static function rowMatchesMasterDefaults(array $row, MasterSession $master): bool
    {
        $submittedSession = trim((string) ($row['session'] ?? ''));
        $submittedTime = trim((string) ($row['course_time'] ?? ''));
        $submittedLimit = (int) ($row['limit'] ?? 0);
        $submittedStatus = isset($row['status']) ? (bool) $row['status'] : true;
        $submittedLink = isset($row['link']) ? trim((string) $row['link']) : null;

        return $master->session_type === $submittedSession
            && (string) $master->time === $submittedTime
            && $master->status === $submittedStatus
            && 100 === $submittedLimit
            && ($submittedLink === '' || $submittedLink === null);
    }

    protected static function buildSessionPayload(Centre $centre, array $row, ?string $syncKey = null): array
    {
        return [
            'master_session_id' => $row['master_session_id'] ?? null,
            'course_id' => null,
            'centre_id' => $centre->id,
            'session_type' => CourseSession::TYPE_CENTRE,
            'centre_sync_key' => self::normalizeSyncKey($syncKey),
            'session' => $row['session'],
            'limit' => $row['limit'],
            'course_time' => $row['course_time'],
            'link' => ($row['link'] ?? '') !== '' ? $row['link'] : null,
            'status' => $row['status'] ? '1' : '0',
        ];
    }

    protected static function markMissingSessionsInactive(Collection $existingSessions, array $retainedSessionIds): void
    {
        $existingSessions
            ->reject(fn ($session) => in_array((int) $session->id, $retainedSessionIds, true))
            ->each(function (CentreSession $session) {
                if ($session->status === false) {
                    return;
                }

                $session->status = false;
                $session->save();
            });
    }

    protected static function normalizeSyncKey(?string $syncKey): string
    {
        $value = trim((string) ($syncKey ?? ''));
        if ($value !== '') {
            return $value;
        }

        return (string) Str::uuid();
    }
}
