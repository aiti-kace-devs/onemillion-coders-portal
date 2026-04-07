<?php

namespace App\Helpers;

use App\Models\Centre;
use App\Models\CentreSession;
use App\Models\CourseSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        return CentreSession::query()
            ->where('centre_id', $centre->id)
            ->select(['id', 'session', 'limit', 'course_time', 'link', 'status'])
            ->orderBy('id')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => (int) $session->id,
                    'session' => (string) $session->session,
                    'limit' => (int) $session->limit,
                    'course_time' => (string) $session->course_time,
                    'link' => $session->link,
                    'status' => $session->status === null ? true : (bool) $session->status,
                ];
            })
            ->values();
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

        $duplicateSessions = $normalizedRows->pluck('session')->duplicates();
        if ($duplicateSessions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'centre_sessions_payload' => 'Each session type can only be added once.',
            ]);
        }

        return $normalizedRows;
    }

    public static function persist(Centre $centre, Collection $rows): void
    {
        DB::transaction(function () use ($centre, $rows) {
            if ($rows->isEmpty()) {
                CentreSession::query()
                    ->where('centre_id', $centre->id)
                    ->delete();
                return;
            }

            $submittedSessionNames = $rows->pluck('session')->values()->all();

            $existingSessions = CentreSession::query()
                ->where('centre_id', $centre->id)
                ->get();

            $existingById = $existingSessions->keyBy('id');
            $existingBySession = $existingSessions->keyBy('session');

            foreach ($rows as $row) {
                $payload = [
                    'course_id' => null,
                    'centre_id' => $centre->id,
                    'session_type' => CourseSession::TYPE_CENTRE,
                    'session' => $row['session'],
                    'limit' => $row['limit'],
                    'course_time' => $row['course_time'],
                    'link' => $row['link'] !== '' ? $row['link'] : null,
                    'status' => $row['status'] ? '1' : '0',
                ];

                if ($row['id'] && $existingById->has($row['id'])) {
                    $session = $existingById->get($row['id']);
                    $session->fill($payload);
                    $session->save();
                    continue;
                }

                $session = $existingBySession->get($row['session']);
                if ($session) {
                    $session->fill($payload);
                    $session->save();
                    continue;
                }

                CentreSession::create($payload);
            }

            CentreSession::query()
                ->where('centre_id', $centre->id)
                ->whereNotIn('session', $submittedSessionNames)
                ->delete();
        });
    }

    public static function syncAfterCrud(?Centre $centre, Collection $rows): void
    {
        if (! $centre instanceof Centre || $rows->isEmpty()) {
            return;
        }

        try {
            self::persist($centre, $rows);
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
}
