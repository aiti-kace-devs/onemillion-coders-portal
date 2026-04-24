<?php

namespace App\Services;

use App\Events\UserRegistered;
use App\Jobs\SendProtocolInvitationEmailJob;
use App\Jobs\SendSMSAfterRegistrationJob;
use App\Models\Oex_exam_master;
use App\Models\OtpVerifiedEmail;
use App\Models\ProtocolActivationHistory;
use App\Models\ProtocolImportBatch;
use App\Models\ProtocolList;
use App\Models\User;
use App\Models\UserExam;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProtocolListService
{
    private const ACTIVATION_SESSION_TTL_MINUTES = 30;
    private const ACTIVATION_MAX_FAILURES = 3;
    private const MAX_FIELD_CHANGE_ATTEMPTS = 2;
    private const INVITE_PUBLIC_ID_BYTES = 12;
    private const INVITE_SIGNATURE_BYTES = 12;

    /**
     * Parse the first sheet from an uploaded spreadsheet, persist a server-side batch record,
     * and normalize rows for the admin preview table.
     */
    public function parseSpreadsheet(UploadedFile $file, ?array $actor = null): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, ['xlsx', 'xlsm', 'xltx', 'xltm'], true) && ! class_exists(\ZipArchive::class)) {
            throw ValidationException::withMessages([
                'file' => ['XLSX upload is unavailable because the PHP zip extension is not enabled. Enable extension=zip in php.ini or upload the sheet as CSV instead.'],
            ]);
        }

        try {
            $sheets = Excel::toArray([], $file);
        } catch (Throwable $exception) {
            if ($this->isMissingZipArchiveException($exception)) {
                throw ValidationException::withMessages([
                    'file' => ['XLSX upload is unavailable because the PHP zip extension is not enabled. Enable extension=zip in php.ini or upload the sheet as CSV instead.'],
                ]);
            }

            throw $exception;
        }

        $sheet = $sheets[0] ?? [];
        $rows = [];

        if (! empty($sheet)) {
            $headers = array_map(fn ($header) => $this->normalizeHeader($header), array_shift($sheet));

            foreach ($sheet as $row) {
                $values = [];
                foreach ($headers as $index => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $values[$header] = $row[$index] ?? null;
                }

                $normalized = $this->normalizeRowPayload($values);
                if ($this->rowIsEmpty($normalized)) {
                    continue;
                }

                $rows[] = $this->serializePreviewRow($normalized);
            }
        }

        $batch = $this->createImportBatch($file, $rows, $actor);

        $rows = array_map(function (array $row) use ($batch) {
            $row['import_batch_id'] = $batch->id;
            $row['import_batch_committed'] = false;

            return $row;
        }, $rows);

        $batch->rows_snapshot = collect($rows)
            ->map(fn (array $row) => Arr::except($row, ['local_key']))
            ->values()
            ->all();
        $batch->save();

        return [
            'rows' => $rows,
            'batch' => $this->serializeImportBatch($batch->fresh()),
        ];
    }

    /**
     * Return pending protocol rows for the admin UI.
     */
    public function pendingRows(): array
    {
        return ProtocolList::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ProtocolList $protocol) => $this->serializeProtocol($protocol))
            ->all();
    }

    /**
     * Return the most recent server-side import batches.
     */
    public function recentImportBatches(int $limit = 6): array
    {
        return ProtocolImportBatch::query()
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ProtocolImportBatch $batch) => $this->serializeImportBatch($batch))
            ->all();
    }

    /**
     * Return recently activated participants for long-term auditability.
     */
    public function recentActivationHistory(int $limit = 10): array
    {
        return ProtocolActivationHistory::query()
            ->orderByDesc('activation_completed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ProtocolActivationHistory $history) => $this->serializeActivationHistory($history))
            ->all();
    }

    /**
     * Validate and save a complete table payload atomically.
     */
    public function saveRows(array $rows, ?array $actor = null): array
    {
        $rows = collect($rows)->values()->map(fn ($row) => $this->normalizeRowPayload((array) $row));
        [$preparedRows, $errors] = $this->prepareRowsForSave($rows);

        if (! empty($errors)) {
            $this->markImportBatchesAsReviewNeeded($errors);

            return [
                'saved' => false,
                'errors' => $errors,
                'rows' => [],
                'import_batches' => $this->recentImportBatches(),
            ];
        }

        $savedRows = [];
        $invitationPayloads = [];
        $batchStats = [];

        DB::transaction(function () use ($preparedRows, &$savedRows, &$invitationPayloads, &$batchStats, $actor) {
            foreach ($preparedRows as $item) {
                /** @var ProtocolList $protocol */
                $protocol = $item['model'];
                $data = $item['data'];
                $wasExisting = $protocol->exists;

                $protocol->fill(Arr::only($data, [
                    'first_name',
                    'middle_name',
                    'last_name',
                    'previous_name',
                    'email',
                    'gender',
                    'age',
                    'mobile_no',
                    'ghcard',
                ]));

                if ($item['import_batch_id']) {
                    $protocol->protocol_import_batch_id = $item['import_batch_id'];
                }

                if ($item['email_changed']) {
                    $protocol->email_change_attempts = (int) $protocol->email_change_attempts + 1;
                }

                if ($item['ghcard_changed']) {
                    $protocol->ghcard_change_attempts = (int) $protocol->ghcard_change_attempts + 1;
                }

                $inviteToken = null;
                if ($item['should_send_invitation']) {
                    $this->resetActivationState($protocol);
                    $this->resetInvitationDeliveryState($protocol, 'queued');
                    $inviteToken = $this->issueInviteToken($protocol);
                }

                $protocol->save();
                $savedRows[] = $protocol->fresh();

                if ($inviteToken !== null) {
                    $invitationPayloads[] = [
                        'id' => $protocol->id,
                        'token' => $inviteToken,
                    ];
                }

                if ($item['track_batch']) {
                    $batchId = $item['import_batch_id'];
                    if (! isset($batchStats[$batchId])) {
                        $batchStats[$batchId] = [
                            'saved_rows' => 0,
                            'created_rows' => 0,
                            'updated_rows' => 0,
                            'invalid_rows' => 0,
                            'invitation_emails_sent' => 0,
                        ];
                    }

                    $batchStats[$batchId]['saved_rows']++;
                    $batchStats[$batchId][$wasExisting ? 'updated_rows' : 'created_rows']++;

                    if ($item['should_send_invitation']) {
                        $batchStats[$batchId]['invitation_emails_sent']++;
                    }
                }
            }

            DB::afterCommit(function () use ($invitationPayloads, $batchStats, $actor) {
                if (! empty($invitationPayloads)) {
                    $protocols = ProtocolList::query()
                        ->whereIn('id', collect($invitationPayloads)->pluck('id'))
                        ->get()
                        ->keyBy('id');

                    foreach ($invitationPayloads as $payload) {
                        /** @var ProtocolList|null $protocol */
                        $protocol = $protocols->get($payload['id']);
                        if ($protocol) {
                            $this->queueActivationInvitation($protocol, $payload['token']);
                        }
                    }
                }

                if (! empty($batchStats)) {
                    $this->markImportBatchesAsApplied($batchStats, $actor);
                }
            });
        });

        return [
            'saved' => true,
            'errors' => [],
            'rows' => collect($savedRows)
                ->map(fn (ProtocolList $protocol) => $this->serializeProtocol($protocol))
                ->sortByDesc('updated_at')
                ->values()
                ->all(),
            'import_batches' => $this->recentImportBatches(),
        ];
    }

    /**
     * Delete a saved protocol row.
     */
    public function deleteRow(ProtocolList $protocol): void
    {
        $protocol->delete();
    }

    /**
     * Open a one-time activation session for a pending participant.
     */
    public function beginActivation(string $token): array
    {
        $publicId = $this->extractValidInvitePublicId($token);
        if ($publicId === null) {
            return $this->invalidActivationState('unknown');
        }

        /** @var ProtocolList|null $protocol */
        $protocol = $this->resolveProtocolByInvitePublicId($publicId);
        if (! $protocol) {
            return $this->inviteExistsInActivationHistory($publicId)
                ? $this->invalidActivationState('used')
                : $this->invalidActivationState('unknown');
        }

        $sessionToken = Str::random(80);
        $protocol->activation_link_opened_at = now();
        $protocol->activation_session_token_hash = hash('sha256', $sessionToken);
        $protocol->activation_session_expires_at = now()->addMinutes(self::ACTIVATION_SESSION_TTL_MINUTES);
        $protocol->failed_activation_attempts = 0;
        $protocol->save();

        return [
            'status' => 'ready',
            'message' => 'Activation session started.',
            'session_token' => $sessionToken,
            'participant' => [
                'first_name' => $protocol->first_name,
                'full_name' => $protocol->full_name,
                'email' => $protocol->email,
                'ghcard' => $protocol->ghcard,
            ],
            'attempts' => [
                'max' => self::ACTIVATION_MAX_FAILURES,
                'remaining' => self::ACTIVATION_MAX_FAILURES,
            ],
        ];
    }

    /**
     * Activate a pending participant and move them into the users table.
     */
    public function activateParticipant(array $payload, array $meta = []): array
    {
        $sessionToken = trim((string) ($payload['session_token'] ?? ''));
        $nationalId = ProtocolList::normalizeGhcard((string) ($payload['national_id'] ?? ''));

        $publicId = $this->extractValidInvitePublicId((string) ($payload['token'] ?? ''));
        if ($publicId === null) {
            return $this->invalidActivationState('unknown');
        }

        /** @var ProtocolList|null $protocol */
        $protocol = $this->resolveProtocolByInvitePublicId($publicId);
        if (! $protocol) {
            return $this->inviteExistsInActivationHistory($publicId)
                ? $this->invalidActivationState('used')
                : $this->invalidActivationState('unknown');
        }

        if (
            $protocol->activation_session_token_hash === null
            || $protocol->activation_session_expires_at === null
            || now()->greaterThan($protocol->activation_session_expires_at)
            || ! hash_equals($protocol->activation_session_token_hash, hash('sha256', $sessionToken))
        ) {
            return $this->invalidActivationState('expired');
        }

        if ($protocol->failed_activation_attempts >= self::ACTIVATION_MAX_FAILURES) {
            return $this->invalidActivationState('locked');
        }

        if ($nationalId !== $protocol->ghcard) {
            $protocol->failed_activation_attempts = (int) $protocol->failed_activation_attempts + 1;
            $protocol->save();

            return [
                'status' => 'validation_error',
                'http_status' => 422,
                'message' => 'National ID does not match the invitation record.',
                'errors' => [
                    'national_id' => ['National ID must match the Ghana Card number stored for this invitation.'],
                ],
                'attempts' => [
                    'max' => self::ACTIVATION_MAX_FAILURES,
                    'remaining' => max(0, self::ACTIVATION_MAX_FAILURES - (int) $protocol->failed_activation_attempts),
                ],
            ];
        }

        $validator = Validator::make($payload, [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'password.confirmed' => 'Confirm password must match the chosen password.',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'validation_error',
                'http_status' => 422,
                'message' => 'Please fix the highlighted fields and try again.',
                'errors' => $validator->errors()->toArray(),
                'attempts' => [
                    'max' => self::ACTIVATION_MAX_FAILURES,
                    'remaining' => max(0, self::ACTIVATION_MAX_FAILURES - (int) $protocol->failed_activation_attempts),
                ],
            ];
        }

        $email = strtolower((string) $protocol->email);
        $ghcard = (string) $protocol->ghcard;

        if (User::query()->where('email', $email)->exists()) {
            return [
                'status' => 'conflict',
                'http_status' => 409,
                'message' => 'An account already exists with this email address. Please contact support.',
            ];
        }

        if (User::query()->where('ghcard', $ghcard)->exists()) {
            return [
                'status' => 'conflict',
                'http_status' => 409,
                'message' => 'An account already exists with this Ghana Card number. Please contact support.',
            ];
        }

        $plainPassword = (string) $payload['password'];
        $createdUser = null;

        DB::transaction(function () use ($protocol, $plainPassword, $meta, &$createdUser) {
            $user = new User();
            $userColumns = $this->userTableColumns();
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'name', $protocol->full_name);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'first_name', $protocol->first_name);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'middle_name', $protocol->middle_name);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'last_name', $protocol->last_name);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'previous_name', $protocol->previous_name);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'email', $protocol->email);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'gender', $protocol->gender);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'age', $protocol->age);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'mobile_no', $protocol->mobile_no);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'ghcard', $protocol->ghcard);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'card_type', 'GHCARD');
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'password', $plainPassword);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'userId', (string) Str::uuid());
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'status', 1);
            $this->applyUserAttributeIfColumnExists($user, $userColumns, 'is_protocol', true);

            $randomExam = Oex_exam_master::query()->inRandomOrder()->first();
            if ($randomExam) {
                $this->applyUserAttributeIfColumnExists($user, $userColumns, 'exam', $randomExam->id);
            }

            $user->save();

            if (Schema::hasTable('otp_verified_emails')) {
                OtpVerifiedEmail::query()->updateOrCreate(
                    ['email' => strtolower((string) $protocol->email)],
                    [
                        'verified_at' => now(),
                        'used_at' => now(),
                    ]
                );
            }

            if ($randomExam) {
                UserExam::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'exam_id' => $randomExam->id,
                    ],
                    [
                        'std_status' => 1,
                        'exam_joined' => 0,
                    ]
                );
            }

            ProtocolActivationHistory::query()->create([
                'protocol_list_id' => $protocol->id,
                'protocol_import_batch_id' => $protocol->protocol_import_batch_id,
                'user_id' => $user->id,
                'user_uuid' => $user->userId,
                'first_name' => $protocol->first_name,
                'middle_name' => $protocol->middle_name,
                'last_name' => $protocol->last_name,
                'previous_name' => $protocol->previous_name,
                'email' => $protocol->email,
                'gender' => $protocol->gender,
                'age' => $protocol->age,
                'mobile_no' => $protocol->mobile_no,
                'ghcard' => $protocol->ghcard,
                'invite_token_hash' => $protocol->invite_token_hash,
                'invitation_email_sent_at' => $protocol->activation_email_sent_at,
                'invitation_email_status' => $protocol->invitation_email_status,
                'invitation_email_queued_at' => $protocol->invitation_email_queued_at,
                'invitation_email_last_attempt_at' => $protocol->invitation_email_last_attempt_at,
                'invitation_email_failed_at' => $protocol->invitation_email_failed_at,
                'invitation_email_attempts' => (int) $protocol->invitation_email_attempts,
                'invitation_email_failure_message' => $protocol->invitation_email_failure_message,
                'activation_link_opened_at' => $protocol->activation_link_opened_at,
                'activation_completed_at' => now(),
                'failed_activation_attempts' => (int) $protocol->failed_activation_attempts,
                'activated_ip_address' => Arr::get($meta, 'ip_address'),
            ]);

            $createdUser = $user;

            DB::afterCommit(function () use ($user, $plainPassword) {
                UserRegistered::dispatch($user, $plainPassword);

                if ((bool) config('SEND_SMS_AFTER_REGISTRATION', true) && ! empty($user->mobile_no)) {
                    $name = trim(preg_replace('/\s+/', ' ', $user->full_name));
                    $smsContent = \App\Helpers\SmsHelper::getTemplate(AFTER_REGISTRATION_SMS, [
                        'name' => $name,
                    ]) ?? '';

                    if ($smsContent !== '') {
                        SendSMSAfterRegistrationJob::dispatch([
                            'message' => $smsContent,
                            'phonenumber' => $user->mobile_no,
                        ]);
                    }
                }
            });

            $protocol->delete();
        });

        return [
            'status' => 'success',
            'http_status' => 200,
            'message' => 'Account activated successfully.',
            'user' => [
                'id' => $createdUser?->id,
                'userId' => $createdUser?->userId,
                'email' => $createdUser?->email,
            ],
        ];
    }

    /**
     * Serialize a saved protocol row for the admin UI.
     */
    public function serializeProtocol(ProtocolList $protocol): array
    {
        return [
            'local_key' => 'saved-' . $protocol->id,
            'id' => $protocol->id,
            'first_name' => $protocol->first_name,
            'middle_name' => $protocol->middle_name,
            'last_name' => $protocol->last_name,
            'previous_name' => $protocol->previous_name,
            'gender' => $protocol->gender,
            'age' => $protocol->age,
            'email' => $protocol->email,
            'mobile_no' => $protocol->mobile_no,
            'ghcard' => $protocol->ghcard,
            'import_batch_id' => $protocol->protocol_import_batch_id,
            'import_batch_committed' => true,
            'email_change_attempts' => (int) $protocol->email_change_attempts,
            'ghcard_change_attempts' => (int) $protocol->ghcard_change_attempts,
            'activation_email_sent_at' => optional($protocol->activation_email_sent_at)?->toIso8601String(),
            'invitation_email_status' => $protocol->invitation_email_status,
            'invitation_email_queued_at' => optional($protocol->invitation_email_queued_at)?->toIso8601String(),
            'invitation_email_last_attempt_at' => optional($protocol->invitation_email_last_attempt_at)?->toIso8601String(),
            'invitation_email_failed_at' => optional($protocol->invitation_email_failed_at)?->toIso8601String(),
            'invitation_email_attempts' => (int) $protocol->invitation_email_attempts,
            'invitation_email_failure_message' => $protocol->invitation_email_failure_message,
            'created_at' => optional($protocol->created_at)?->toIso8601String(),
            'updated_at' => optional($protocol->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * Build a user-facing activation URL for a signed opaque invitation token.
     */
    public function activationUrlFor(string $inviteToken): string
    {
        $base = rtrim((string) config('app.quiz_frontend_url', config('app.url', '')), '/');

        return $base . '/a/' . rawurlencode($inviteToken);
    }

    private function prepareRowsForSave(Collection $rows): array
    {
        $errors = [];
        $prepared = [];
        $payloadDuplicates = $this->detectPayloadDuplicates($rows);

        foreach ($rows as $index => $row) {
            $rowErrors = $payloadDuplicates[$index] ?? [];
            $validator = Validator::make($row, [
                'first_name' => ['required', 'string', 'max:255'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'previous_name' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'gender' => ['required', Rule::in(['male', 'female'])],
                'age' => ['nullable', 'integer', 'min:0', 'max:120'],
                'mobile_no' => ['required', 'string', 'max:30'],
                'ghcard' => ['required', 'string', 'max:20'],
            ]);

            if (! ProtocolList::isValidGhcard($row['ghcard'])) {
                $validator->errors()->add('ghcard', 'Ghana Card number must match the format GHA-123456789-0.');
            }

            $existing = $this->resolveExistingRow($row);
            if ($existing['conflict']) {
                $rowErrors['email'][] = 'This row matches different saved participants by email and Ghana Card.';
                $rowErrors['ghcard'][] = 'This row matches different saved participants by email and Ghana Card.';
            }

            /** @var ProtocolList $model */
            $model = $existing['model'] ?? new ProtocolList();
            $rowErrors = array_merge_recursive($rowErrors, $validator->errors()->toArray());

            $emailChanged = $model->exists && $model->email !== $row['email'];
            $ghcardChanged = $model->exists && $model->ghcard !== $row['ghcard'];

            if ($emailChanged && (int) $model->email_change_attempts >= self::MAX_FIELD_CHANGE_ATTEMPTS) {
                $rowErrors['email'][] = 'Email can only be changed twice for a saved participant.';
            }

            if ($ghcardChanged && (int) $model->ghcard_change_attempts >= self::MAX_FIELD_CHANGE_ATTEMPTS) {
                $rowErrors['ghcard'][] = 'Ghana Card number can only be changed twice for a saved participant.';
            }

            $emailConflictInProtocol = ProtocolList::query()
                ->where('email', $row['email'])
                ->when($model->exists, fn ($query) => $query->where('id', '!=', $model->id))
                ->exists();
            if ($emailConflictInProtocol) {
                $rowErrors['email'][] = 'This email already exists in the pending protocol list.';
            }

            $ghcardConflictInProtocol = ProtocolList::query()
                ->where('ghcard', $row['ghcard'])
                ->when($model->exists, fn ($query) => $query->where('id', '!=', $model->id))
                ->exists();
            if ($ghcardConflictInProtocol) {
                $rowErrors['ghcard'][] = 'This Ghana Card number already exists in the pending protocol list.';
            }

            if (User::query()->where('email', $row['email'])->exists()) {
                $rowErrors['email'][] = 'This email already belongs to a registered user.';
            }

            if (User::query()->where('ghcard', $row['ghcard'])->exists()) {
                $rowErrors['ghcard'][] = 'This Ghana Card number already belongs to a registered user.';
            }

            if (! empty($rowErrors)) {
                $errors[] = [
                    'row' => $index + 1,
                    'local_key' => $row['local_key'] ?: ('row-' . $index),
                    'import_batch_id' => $row['import_batch_id'] && ! $row['import_batch_committed'] ? $row['import_batch_id'] : null,
                    'messages' => $this->flattenErrorMessages($rowErrors),
                    'fields' => $rowErrors,
                ];
                continue;
            }

            $prepared[] = [
                'model' => $model,
                'data' => $row,
                'import_batch_id' => $row['import_batch_id'],
                'track_batch' => ! empty($row['import_batch_id']) && ! $row['import_batch_committed'],
                'email_changed' => $emailChanged,
                'ghcard_changed' => $ghcardChanged,
                'should_send_invitation' => ! $model->exists || $emailChanged || $ghcardChanged,
            ];
        }

        return [$prepared, $errors];
    }

    private function resolveExistingRow(array $row): array
    {
        $byId = null;
        if (! empty($row['id'])) {
            $byId = ProtocolList::query()->find($row['id']);
        }

        $byEmail = ProtocolList::query()->where('email', $row['email'])->first();
        $byGhcard = ProtocolList::query()->where('ghcard', $row['ghcard'])->first();

        $candidates = collect([$byId, $byEmail, $byGhcard])
            ->filter()
            ->unique('id')
            ->values();

        return [
            'model' => $candidates->first(),
            'conflict' => $candidates->count() > 1,
        ];
    }

    private function detectPayloadDuplicates(Collection $rows): array
    {
        $errors = [];
        $emailMap = [];
        $ghcardMap = [];

        foreach ($rows as $index => $row) {
            if (! empty($row['email'])) {
                $emailMap[$row['email']][] = $index;
            }

            if (! empty($row['ghcard'])) {
                $ghcardMap[$row['ghcard']][] = $index;
            }
        }

        foreach ($emailMap as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $errors[$index]['email'][] = 'This email appears more than once in the current table.';
            }
        }

        foreach ($ghcardMap as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $errors[$index]['ghcard'][] = 'This Ghana Card number appears more than once in the current table.';
            }
        }

        return $errors;
    }

    private function createImportBatch(UploadedFile $file, array $rows, ?array $actor = null): ProtocolImportBatch
    {
        return ProtocolImportBatch::query()->create([
            'batch_uuid' => (string) Str::uuid(),
            'source_filename' => $file->getClientOriginalName(),
            'source_extension' => strtolower((string) $file->getClientOriginalExtension()) ?: null,
            'uploaded_by_admin_id' => Arr::get($actor, 'id'),
            'uploaded_by_admin_name' => Arr::get($actor, 'name'),
            'status' => ProtocolImportBatch::STATUS_PARSED,
            'total_rows' => count($rows),
            'rows_snapshot' => collect($rows)
                ->map(fn (array $row) => Arr::except($row, ['local_key']))
                ->values()
                ->all(),
            'uploaded_at' => now(),
        ]);
    }

    private function markImportBatchesAsReviewNeeded(array $errors): void
    {
        $grouped = collect($errors)
            ->filter(fn (array $error) => ! empty($error['import_batch_id']))
            ->groupBy('import_batch_id');

        if ($grouped->isEmpty()) {
            return;
        }

        $batches = ProtocolImportBatch::query()
            ->whereIn('id', $grouped->keys())
            ->get()
            ->keyBy('id');

        foreach ($grouped as $batchId => $batchErrors) {
            /** @var ProtocolImportBatch|null $batch */
            $batch = $batches->get((int) $batchId);
            if (! $batch) {
                continue;
            }

            $batch->status = ProtocolImportBatch::STATUS_REVIEW_NEEDED;
            $batch->invalid_rows = $batchErrors->count();
            $batch->error_snapshot = $batchErrors
                ->map(fn (array $error) => Arr::only($error, ['row', 'messages']))
                ->values()
                ->all();
            $batch->applied_at = null;
            $batch->applied_by_admin_id = null;
            $batch->applied_by_admin_name = null;
            $batch->save();
        }
    }

    private function markImportBatchesAsApplied(array $batchStats, ?array $actor = null): void
    {
        $batches = ProtocolImportBatch::query()
            ->whereIn('id', array_keys($batchStats))
            ->get()
            ->keyBy('id');

        foreach ($batchStats as $batchId => $stats) {
            /** @var ProtocolImportBatch|null $batch */
            $batch = $batches->get((int) $batchId);
            if (! $batch) {
                continue;
            }

            $batch->status = ProtocolImportBatch::STATUS_APPLIED;
            $batch->saved_rows = (int) ($stats['saved_rows'] ?? 0);
            $batch->created_rows = (int) ($stats['created_rows'] ?? 0);
            $batch->updated_rows = (int) ($stats['updated_rows'] ?? 0);
            $batch->invalid_rows = 0;
            $batch->invitation_emails_sent = (int) ($stats['invitation_emails_sent'] ?? 0);
            $batch->error_snapshot = null;
            $batch->applied_by_admin_id = Arr::get($actor, 'id');
            $batch->applied_by_admin_name = Arr::get($actor, 'name');
            $batch->applied_at = now();
            $batch->save();
        }
    }

    private function serializeImportBatch(ProtocolImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'batch_uuid' => $batch->batch_uuid,
            'reference' => strtoupper(substr((string) $batch->batch_uuid, 0, 8)),
            'source_filename' => $batch->source_filename,
            'source_extension' => $batch->source_extension,
            'status' => $batch->status,
            'total_rows' => (int) $batch->total_rows,
            'saved_rows' => (int) $batch->saved_rows,
            'created_rows' => (int) $batch->created_rows,
            'updated_rows' => (int) $batch->updated_rows,
            'invalid_rows' => (int) $batch->invalid_rows,
            'invitation_emails_sent' => (int) $batch->invitation_emails_sent,
            'uploaded_by_admin_name' => $batch->uploaded_by_admin_name,
            'applied_by_admin_name' => $batch->applied_by_admin_name,
            'uploaded_at' => optional($batch->uploaded_at)?->toIso8601String(),
            'applied_at' => optional($batch->applied_at)?->toIso8601String(),
        ];
    }

    private function serializeActivationHistory(ProtocolActivationHistory $history): array
    {
        return [
            'id' => $history->id,
            'full_name' => $history->full_name,
            'email' => $history->email,
            'ghcard' => $history->ghcard,
            'mobile_no' => $history->mobile_no,
            'user_id' => $history->user_id,
            'user_uuid' => $history->user_uuid,
            'protocol_import_batch_id' => $history->protocol_import_batch_id,
            'activation_completed_at' => optional($history->activation_completed_at)?->toIso8601String(),
            'invitation_email_sent_at' => optional($history->invitation_email_sent_at)?->toIso8601String(),
            'invitation_email_status' => $history->invitation_email_status,
            'invitation_email_queued_at' => optional($history->invitation_email_queued_at)?->toIso8601String(),
            'invitation_email_last_attempt_at' => optional($history->invitation_email_last_attempt_at)?->toIso8601String(),
            'invitation_email_failed_at' => optional($history->invitation_email_failed_at)?->toIso8601String(),
            'invitation_email_attempts' => (int) $history->invitation_email_attempts,
            'invitation_email_failure_message' => $history->invitation_email_failure_message,
            'activation_link_opened_at' => optional($history->activation_link_opened_at)?->toIso8601String(),
        ];
    }

    private function resetActivationState(ProtocolList $protocol): void
    {
        $protocol->activation_link_opened_at = null;
        $protocol->activation_session_token_hash = null;
        $protocol->activation_session_expires_at = null;
        $protocol->failed_activation_attempts = 0;
    }

    private function resetInvitationDeliveryState(ProtocolList $protocol, string $status = 'pending'): void
    {
        $protocol->activation_email_sent_at = null;
        $protocol->invitation_email_status = $status;
        $protocol->invitation_email_queued_at = now();
        $protocol->invitation_email_last_attempt_at = null;
        $protocol->invitation_email_failed_at = null;
        $protocol->invitation_email_attempts = 0;
        $protocol->invitation_email_failure_message = null;
    }

    private function issueInviteToken(ProtocolList $protocol): string
    {
        $publicId = $this->randomBase64Url(self::INVITE_PUBLIC_ID_BYTES);
        $protocol->invite_token_hash = hash('sha256', $publicId);
        $protocol->invite_token_issued_at = now();

        return $publicId . '.' . $this->signInvitePublicId($publicId);
    }

    private function resolveProtocolByInviteToken(string $token): ?ProtocolList
    {
        $publicId = $this->extractValidInvitePublicId($token);
        if ($publicId === null) {
            return null;
        }

        return $this->resolveProtocolByInvitePublicId($publicId);
    }

    private function resolveProtocolByInvitePublicId(string $publicId): ?ProtocolList
    {
        return ProtocolList::query()
            ->where('invite_token_hash', hash('sha256', $publicId))
            ->first();
    }

    private function inviteExistsInActivationHistory(string $publicId): bool
    {
        return ProtocolActivationHistory::query()
            ->where('invite_token_hash', hash('sha256', $publicId))
            ->exists();
    }

    private function extractValidInvitePublicId(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$publicId, $signature] = $parts;

        if (! preg_match('/^[A-Za-z0-9\-_]{12,128}$/', $publicId)) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9\-_]{12,128}$/', $signature)) {
            return null;
        }

        $expectedSignatures = [
            $this->signInvitePublicId($publicId),
            $this->signInvitePublicIdLegacy($publicId),
        ];

        foreach ($expectedSignatures as $expectedSignature) {
            if (hash_equals($expectedSignature, $signature)) {
                return $publicId;
            }
        }

        return null;
    }

    private function signInvitePublicId(string $publicId): string
    {
        $signature = hash_hmac('sha256', $publicId, $this->inviteSigningKey(), true);

        return $this->base64UrlEncode(substr($signature, 0, self::INVITE_SIGNATURE_BYTES));
    }

    private function signInvitePublicIdLegacy(string $publicId): string
    {
        $signature = hash_hmac('sha256', $publicId, $this->inviteSigningKey(), true);

        return $this->base64UrlEncode($signature);
    }

    private function inviteSigningKey(): string
    {
        return 'protocol-invite|' . (string) config('app.key');
    }

    private function queueActivationInvitation(ProtocolList $protocol, string $inviteToken): void
    {
        SendProtocolInvitationEmailJob::dispatch($protocol->id, $inviteToken)
            ->onConnection($this->protocolInvitationQueueConnection())
            ->onQueue('protocol-emails');
    }

    private function protocolInvitationQueueConnection(): string
    {
        $connection = (string) config('queue.default', 'database');

        return $connection === 'sync' ? 'database' : $connection;
    }

    private function userTableColumns(): array
    {
        return array_flip(Schema::getColumnListing('users'));
    }

    private function applyUserAttributeIfColumnExists(User $user, array $columns, string $column, mixed $value): void
    {
        if (isset($columns[$column])) {
            $user->{$column} = $value;
        }
    }

    private function randomBase64Url(int $bytes): string
    {
        return $this->base64UrlEncode(random_bytes($bytes));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function invalidActivationState(string $state): array
    {
        return match ($state) {
            'used' => [
                'status' => 'used',
                'http_status' => 410,
                'message' => 'This secure activation link has already been used to complete account activation and can no longer be reused.',
            ],
            'expired' => [
                'status' => 'expired',
                'http_status' => 410,
                'message' => 'This activation session has expired. Please contact the administrator for a new invitation.',
            ],
            'locked' => [
                'status' => 'locked',
                'http_status' => 423,
                'message' => 'Activation is locked after too many failed attempts. Please contact the administrator for a new invitation.',
            ],
            default => [
                'status' => 'unknown',
                'http_status' => 404,
                'message' => 'We could not find a matching activation invitation for this link.',
            ],
        };
    }

    private function normalizeRowPayload(array $row): array
    {
        $mapped = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeader($key);
            $mapped[$normalizedKey] = $value;
        }

        $normalized = [
            'local_key' => (string) ($mapped['local_key'] ?? $mapped['client_key'] ?? ''),
            'id' => isset($mapped['id']) && $mapped['id'] !== '' ? (int) $mapped['id'] : null,
            'first_name' => $this->cleanString($this->firstNonEmpty($mapped, ['first_name', 'firstname', 'first'])),
            'middle_name' => $this->cleanString($this->firstNonEmpty($mapped, ['middle_name', 'middlename', 'middle'])),
            'last_name' => $this->cleanString($this->firstNonEmpty($mapped, ['last_name', 'lastname', 'surname', 'last'])),
            'previous_name' => $this->cleanString($this->firstNonEmpty($mapped, ['previous_name', 'previousname'])),
            'email' => strtolower((string) $this->cleanString($this->firstNonEmpty($mapped, ['email', 'email_address']))),
            'gender' => strtolower((string) $this->cleanString($this->firstNonEmpty($mapped, ['gender', 'sex']))),
            'age' => $this->normalizeAge($this->firstNonEmpty($mapped, ['age'])),
            'mobile_no' => $this->cleanString($this->firstNonEmpty($mapped, ['mobile_no', 'mobile', 'phone', 'phone_number', 'mobile_number'])),
            'ghcard' => ProtocolList::normalizeGhcard($this->cleanString($this->firstNonEmpty($mapped, [
                'ghcard',
                'ghana_card',
                'ghana_card_number',
                'ghana_card_no',
                'ghana_card_id',
                'gh_card_no',
            ]))),
            'import_batch_id' => isset($mapped['import_batch_id']) && $mapped['import_batch_id'] !== ''
                ? (int) $mapped['import_batch_id']
                : (isset($mapped['protocol_import_batch_id']) && $mapped['protocol_import_batch_id'] !== ''
                    ? (int) $mapped['protocol_import_batch_id']
                    : null),
            'import_batch_committed' => $this->normalizeBoolean($mapped['import_batch_committed'] ?? false),
        ];

        if ($normalized['local_key'] === '') {
            $normalized['local_key'] = (string) Str::uuid();
        }

        if ($normalized['gender'] === 'm') {
            $normalized['gender'] = 'male';
        }

        if ($normalized['gender'] === 'f') {
            $normalized['gender'] = 'female';
        }

        return $normalized;
    }

    private function serializePreviewRow(array $row): array
    {
        return array_merge($row, [
            'import_batch_id' => $row['import_batch_id'] ?? null,
            'import_batch_committed' => false,
            'email_change_attempts' => 0,
            'ghcard_change_attempts' => 0,
            'activation_email_sent_at' => null,
            'invitation_email_status' => 'pending',
            'invitation_email_queued_at' => null,
            'invitation_email_last_attempt_at' => null,
            'invitation_email_failed_at' => null,
            'invitation_email_attempts' => 0,
            'invitation_email_failure_message' => null,
            'created_at' => null,
            'updated_at' => null,
        ]);
    }

    private function normalizeHeader($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);

        return trim((string) $value, '_');
    }

    private function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeAge($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeBoolean($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach (['first_name', 'middle_name', 'last_name', 'previous_name', 'email', 'gender', 'age', 'mobile_no', 'ghcard'] as $field) {
            if ($row[$field] !== null && $row[$field] !== '') {
                return false;
            }
        }

        return true;
    }

    private function firstNonEmpty(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            if ($row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function flattenErrorMessages(array $errors): array
    {
        return collect($errors)
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    private function isMissingZipArchiveException(Throwable $exception): bool
    {
        do {
            $message = $exception->getMessage();
            if (str_contains($message, 'ZipArchive') && str_contains($message, 'not found')) {
                return true;
            }

            $exception = $exception->getPrevious();
        } while ($exception !== null);

        return false;
    }
}
