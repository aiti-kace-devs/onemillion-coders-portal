<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Models\GhanaCardVerification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GhanaCardService
{
    public const BLOCK_REASON_WATCHLIST = 'watchlist';
    public const BLOCK_REASON_ATTEMPTS_EXCEEDED = 'attempts_exceeded';
    public const BLOCK_REASON_NAME_MISMATCH = 'name_mismatch';
    public const BLOCK_REASON_IDENTITY_MISMATCH = 'identity_mismatch';
    public const BLOCK_REASON_NON_GHANAIAN = 'non_ghanaian';

    private const CODE_NAME_MISMATCH = '10';
    private const CODE_IDENTITY_MISMATCH = '11';
    private const CODE_NON_GHANAIAN = '12';

    protected string $baseUrl;
    protected string $merchantKey;
    private const DEFAULT_IMAGE_DISK = 'private_cloud';
    private const DEFAULT_IMAGE_DIR = 'omcp/users-profile';

    public function __construct()
    {
        $this->baseUrl = trim((string) config('services.ghana_card.base_url', ''));
        $this->merchantKey = trim((string) config('services.ghana_card.merchant_key', ''));
    }

    /**
     * Process image and verify Ghana Card.
     */
    public function verify(User $user, $imageFile): GhanaCardVerification
    {
        if ($this->baseUrl === '') {
            throw new \RuntimeException('Ghana Card API URL is not configured. Set GHANA_CARD_API_BASE_URL in backend/.env.');
        }

        if (! filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Ghana Card API URL is invalid. Please check GHANA_CARD_API_BASE_URL in backend/.env.');
        }

        if ($this->merchantKey === '') {
            throw new \RuntimeException('Ghana Card merchant key is not configured. Set GHANA_CARD_MERCHANT_KEY in backend/.env.');
        }

        // 1. Process image
        $processedImage = $this->processImage($imageFile);
        $base64Image = base64_encode($processedImage);

        // 2. Prepare Payload
        $payload = [
            'pinNumber' => $user->ghcard,
            'image' => $base64Image,
            'dataType' => 'PNG',
            'center' => 'BRANCHLESS',
            'userID' => (string) ($user->student_id ?? $user->userId),
            'merchantKey' => $this->merchantKey,
        ];

        $verification = GhanaCardVerification::create([
            'user_id' => $user->id,
            'pin_number' => $user->ghcard,
            'request_timestamp' => now(),
        ]);

        try {
            // 3. Call API
            $response = Http::timeout(30)->post($this->baseUrl, $payload);
            $data = $response->json();

            Log::info('Ghana Card Verification Response', $data);

            // 4. Update Verification Record
            // Note: transactionGuid, success, and code are at the root. msg is also root.
            // person is inside data.
            $verification->update([
                'transaction_guid' => $data['data']['transactionGuid'] ?? $data['transactionGuid'] ?? null,
                'response_timestamp' => now(),
                'success' => $data['success'] ?? false,
                'verified' => ($data['data']['verified'] ?? 'FALSE') === 'TRUE',
                'code' => $data['code'] ?? '99', // Default to 99 if missing
                'person_data' => $data['data']['person'] ?? null,
                'status_message' => $this->normalizeStatusMessage(
                    ($data['success'] ?? false) ? 'Success' : 'API Error: ' . ($data['msg'] ?? 'Unknown Error')
                ),
                'pin_number' => $data['data']['person']['cardId'] ?? $data['data']['person']['nationalId'] ?? $verification->pin_number,
            ]);

            // 5. Handle Business Logic based on codes
            $this->handleResponseCode($user, $verification);
        } catch (\Exception $e) {
            Log::error('Ghana Card Verification Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            try {
                $verification->update([
                    'status_message' => $this->normalizeStatusMessage(
                        'Exception: ' . get_class($e) . ': ' . $e->getMessage()
                    ),
                    'code' => '04', // Map to Internal Error
                ]);
            } catch (\Throwable $writeException) {
                // If persisting error details fails, we still don't want to crash the request/job.
                Log::error('Failed to persist Ghana Card verification error details', [
                    'user_id' => $user->id,
                    'verification_id' => $verification->id,
                    'error' => $writeException->getMessage(),
                    'exception' => get_class($writeException),
                ]);
            }
        }

        return $verification;
    }

    /**
     * Process image: resize/crop to 640x480 and convert to PNG.
     */
    protected function processImage($imageFile): string
    {
        // Use a dedicated manager so we control driver selection per runtime.
        $driver = extension_loaded('imagick') ? 'imagick' : 'gd';
        $manager = new ImageManager(['driver' => $driver]);
        $img = $manager->make($imageFile);

        // Crop/Resize to 640x480
        // fit() resizes and crops to reach exactly the desired dimensions
        $img->fit(640, 480);

        // Encode as PNG
        return (string) $img->encode('png');
    }

    /**
     * Handle business logic based on API response codes.
     */
    protected function handleResponseCode(User $user, GhanaCardVerification $verification): void
    {
        $code = $verification->code;

        if ($code === '03') {
            $this->blockUser(
                $user,
                self::BLOCK_REASON_WATCHLIST,
                'Your verification is currently blocked. Please contact support or the NIA office for assistance.'
            );
            return;
        }

        if ($code === '00' && $verification->verified) {
            $person = is_array($verification->person_data) ? $verification->person_data : [];
            $eligibility = $this->evaluateEligibility($user, $person);

            if (! $eligibility['allow']) {
                $this->blockUser($user, $eligibility['reason'], $eligibility['message']);
                $verification->update([
                    'success' => false,
                    'verified' => false,
                    'code' => $eligibility['code'],
                    'status_message' => $eligibility['message'],
                ]);
                return;
            }

            if (! empty($person)) {
                // 1. Sync Personal Data
                $this->syncUserData($user, $person);

                // 2. Store NIA Face Image
                if (isset($person['biometricFeed']['face']['data'])) {
                    $this->saveNiaFaceImage($user, $person['biometricFeed']['face']['data']);
                }
            }

            // Successful and eligible verification should clear non-attempt blocks.
            $this->clearVerificationBlockState($user, false);
        }
    }

    private function normalizeStatusMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = trim($message);
        if ($message === '') {
            return null;
        }

        // Guardrail: never let an unexpectedly huge upstream string break persistence.
        return Str::limit($message, 2000, '…');
    }

    /**
     * Synchronize personal data from NIA response to User model.
     */
    protected function syncUserData(User $user, array $person): void
    {
        try {
            // Set sync flag to bypass model-level protection for verified users
            $user->is_nia_syncing = true;
            $nameData = $this->extractNameParts($person, $user);
            $userData = [
                'first_name' => $nameData['first_name'] ?: $user->first_name,
                'middle_name' => $nameData['middle_name'] ?: $user->middle_name,
                'last_name' => $nameData['last_name'] ?: $user->last_name,
                'name' => $nameData['name'] ?: $user->name,
            ];

            if (! empty($nameData['previous_name'])) {
                $userData['previous_name'] = $nameData['previous_name'];
            }

            // Handle Gender if present
            if (isset($person['gender'])) {
                $gender = strtolower($person['gender']);
                if (in_array($gender, ['male', 'female'])) {
                    $userData['gender'] = $gender;
                }
            }

            // Store Date of Birth in 'data' JSON column
            if (isset($person['birthDate'])) {
                $currentData = $user->data ?? [];
                $currentData['date_of_birth'] = $person['birthDate'];
                $userData['data'] = $currentData;
            }

            $user->update($userData);
        } finally {
            // Re-enable protection
            $user->is_nia_syncing = false;
            $user->saveQuietly();
        }
    }

    /**
     * Decode base64 NIA image and store it in private cloud.
     */
    protected function saveNiaFaceImage(User $user, string $base64Image): void
    {
        try {
            $imageData = base64_decode($base64Image);
            if ($imageData) {
                $studentId = $user->student_id ?? $user->userId;
                $storage = $this->storeVerifiedProfileImage($studentId, $imageData, 'png');
                $this->persistImageMetadata($user, $storage);

                Log::info('Stored NIA verified image for student', [
                    'path' => $storage['path'],
                    'disk' => $storage['disk'],
                    'user_id' => $user->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store NIA face image', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function isVerified(User $user): bool
    {
        if ($user->is_verification_blocked) {
            return false;
        }

        return GhanaCardVerification::query()
            ->where('user_id', $user->id)
            ->where('code', '00')
            ->where('verified', true)
            ->exists();
    }

    public function buildStatus(User $user): array
    {
        $maxAttempts = $this->maxAttempts($user);
        $usedAttempts = $this->failedAttempts($user);
        $this->syncAttemptLimitBlockState($user, $usedAttempts, $maxAttempts);
        $user->refresh();
        $latest = GhanaCardVerification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $imageInfo = $this->resolveProfileImageInfo($user);

        return [
            'verified' => $this->isVerified($user),
            'blocked' => (bool) $user->is_verification_blocked,
            'block' => [
                'reason' => $user->verification_block_reason,
                'reason_label' => $this->resolveBlockReasonLabel($user->verification_block_reason),
                'message' => $this->resolveBlockMessage($user),
            ],
            'attempts' => [
                'used' => $usedAttempts,
                'max' => $maxAttempts,
                'remaining' => max(0, $maxAttempts - $usedAttempts),
            ],
            'latest_attempt' => $latest ? [
                'code' => $latest->code,
                'success' => (bool) $latest->success,
                'verified' => (bool) $latest->verified,
                'status_message' => $latest->status_message,
                'user_message' => $this->buildUserSafeStatusMessage($latest->code, (string) $latest->status_message),
                'response_timestamp' => optional($latest->response_timestamp)?->toIso8601String(),
            ] : null,
            'profile' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'previous_name' => $user->previous_name,
                'date_of_birth' => data_get($user->data ?? [], 'date_of_birth'),
            ],
            'image' => $imageInfo,
        ];
    }

    private function buildUserSafeStatusMessage(?string $code, string $rawStatusMessage): string
    {
        if ($code === '00') {
            return 'Verification successful.';
        }

        if ($code === self::CODE_NAME_MISMATCH) {
            return 'The name on the submitted Ghana Card does not match your profile details. Please contact support for review.';
        }

        if ($code === self::CODE_IDENTITY_MISMATCH) {
            return 'Your verification details do not match your profile identity. Please contact support for redress.';
        }

        if ($code === self::CODE_NON_GHANAIAN) {
            return 'This programme is currently available to Ghanaian nationals only.';
        }

        if ($code === '03') {
            return 'Your verification is currently blocked. Please contact support or the NIA office for assistance.';
        }

        if (str_contains(strtolower($rawStatusMessage), 'maximum number of failed verification attempts')) {
            return 'You have reached the maximum number of verification attempts. Please contact support.';
        }

        return 'Verification could not be completed right now. Please try again shortly or contact support if this continues.';
    }

    public function failedAttempts(User $user): int
    {
        return $this->attemptBaseQuery($user)
            ->where(function ($query) {
                $query->where('code', '!=', '00')
                    ->orWhere('verified', false);
            })
            ->count();
    }

    public function maxAttempts(?User $user = null): int
    {
        $baseAttempts = (int) AppConfig::getValue(GHANA_CARD_MAX_ATTEMPTS, config('GHANA_CARD_MAX_ATTEMPTS', 5));
        if (! $user) {
            return $baseAttempts;
        }

        $extraAttempts = $this->extraAttempts($user);
        return $baseAttempts + $extraAttempts;
    }

    public function blockUser(User $user, string $reason, string $message): void
    {
        $user->update([
            'is_verification_blocked' => true,
            'verification_block_reason' => $reason,
            'verification_block_message' => $message,
        ]);
    }

    public function blockForAttemptsExceeded(User $user, int $maxAttempts): void
    {
        $this->blockUser(
            $user,
            self::BLOCK_REASON_ATTEMPTS_EXCEEDED,
            "You have exhausted all verification attempts ({$maxAttempts}/{$maxAttempts}). Please contact support or an administrator for additional attempts."
        );
    }

    public function resetVerificationBlock(User $user, bool $resetAttempts = true): void
    {
        $this->clearVerificationBlockState($user, $resetAttempts);
    }

    private function clearVerificationBlockState(User $user, bool $resetAttempts): void
    {
        $attributes = [
            'is_verification_blocked' => false,
            'verification_block_reason' => null,
            'verification_block_message' => null,
        ];

        if ($resetAttempts) {
            $attributes['verification_attempts_reset_at'] = now();
        }

        $user->update($attributes);
    }

    private function resolveBlockMessage(User $user): ?string
    {
        if (! $user->is_verification_blocked) {
            return null;
        }

        if (! empty($user->verification_block_message)) {
            return (string) $user->verification_block_message;
        }

        if ($user->verification_block_reason === self::BLOCK_REASON_ATTEMPTS_EXCEEDED) {
            return 'Verification is blocked because you have exhausted all allowed attempts. Please contact support or an administrator.';
        }

        return 'Your verification is currently blocked. Please contact support or an administrator.';
    }

    public function addExtraAttempts(User $user, int $count): void
    {
        $count = max(0, $count);
        if ($count === 0) {
            return;
        }

        $data = is_array($user->data) ? $user->data : [];
        $data['verification_extra_attempts'] = $this->extraAttempts($user) + $count;

        $updates = ['data' => $data];
        if (
            $user->is_verification_blocked
            && $user->verification_block_reason === self::BLOCK_REASON_ATTEMPTS_EXCEEDED
        ) {
            $updates['is_verification_blocked'] = false;
            $updates['verification_block_reason'] = null;
            $updates['verification_block_message'] = null;
        }

        $user->update($updates);
    }

    private function extraAttempts(User $user): int
    {
        return max(0, (int) data_get($user->data ?? [], 'verification_extra_attempts', 0));
    }

    private function syncAttemptLimitBlockState(User $user, int $usedAttempts, int $maxAttempts): void
    {
        $attemptsExceeded = $usedAttempts >= $maxAttempts;
        $isAttemptsBlock =
            $user->is_verification_blocked
            && $user->verification_block_reason === self::BLOCK_REASON_ATTEMPTS_EXCEEDED;

        // Condition lifted: automatically clear stale attempts-exceeded block.
        if ($isAttemptsBlock && ! $attemptsExceeded) {
            $user->update([
                'is_verification_blocked' => false,
                'verification_block_reason' => null,
                'verification_block_message' => null,
            ]);
            return;
        }

        if (! $attemptsExceeded) {
            return;
        }

        if (
            $user->is_verification_blocked
            && $user->verification_block_reason !== self::BLOCK_REASON_ATTEMPTS_EXCEEDED
        ) {
            return;
        }

        if (! $user->is_verification_blocked || $user->verification_block_reason === self::BLOCK_REASON_ATTEMPTS_EXCEEDED) {
            $this->blockForAttemptsExceeded($user, $maxAttempts);
        }
    }

    private function resolveBlockReasonLabel(?string $reason): ?string
    {
        return match ($reason) {
            self::BLOCK_REASON_ATTEMPTS_EXCEEDED => 'Exhausted all attempts',
            self::BLOCK_REASON_WATCHLIST => 'NIA watchlist restriction',
            self::BLOCK_REASON_NAME_MISMATCH => 'Name mismatch',
            self::BLOCK_REASON_IDENTITY_MISMATCH => 'Identity mismatch',
            self::BLOCK_REASON_NON_GHANAIAN => 'Non-Ghanaian nationality',
            default => null,
        };
    }

    private function evaluateEligibility(User $user, array $person): array
    {
        $nameCheck = $this->isNameMatchAllowed((string) $user->name, $person);
        $incomingGender = $this->normalizeGender((string) ($person['gender'] ?? ''));
        $currentGender = $this->normalizeGender((string) ($user->gender ?? ''));
        $genderMismatch = $incomingGender !== null && $currentGender !== null && $incomingGender !== $currentGender;

        if (! $nameCheck && $genderMismatch) {
            return [
                'allow' => false,
                'reason' => self::BLOCK_REASON_IDENTITY_MISMATCH,
                'code' => self::CODE_IDENTITY_MISMATCH,
                'message' => 'Your submitted verification details do not match your profile identity. Please contact support for redress.',
            ];
        }

        if (! $nameCheck) {
            return [
                'allow' => false,
                'reason' => self::BLOCK_REASON_NAME_MISMATCH,
                'code' => self::CODE_NAME_MISMATCH,
                'message' => 'The name on the submitted Ghana Card does not match your profile details. Please contact support for review.',
            ];
        }

        $nationality = $this->extractNationality($person);
        if ($nationality !== null && $nationality !== 'ghanaian') {
            return [
                'allow' => false,
                'reason' => self::BLOCK_REASON_NON_GHANAIAN,
                'code' => self::CODE_NON_GHANAIAN,
                'message' => 'This programme is currently available to Ghanaian nationals only.',
            ];
        }

        return [
            'allow' => true,
            'reason' => null,
            'code' => null,
            'message' => null,
        ];
    }

    private function isNameMatchAllowed(string $currentName, array $person): bool
    {
        $incomingName = trim((string) ($person['fullName'] ?? ''));
        if ($incomingName === '') {
            $incomingName = trim(implode(' ', array_filter([
                $person['firstName'] ?? null,
                $person['middleName'] ?? null,
                $person['surname'] ?? $person['lastName'] ?? null,
                $person['forenames'] ?? null,
            ])));
        }

        $sourceTokens = $this->normalizeNameTokens($currentName);
        $incomingTokens = $this->normalizeNameTokens($incomingName);

        if ($sourceTokens === [] || $incomingTokens === []) {
            return false;
        }

        $sortedSource = $sourceTokens;
        $sortedIncoming = $incomingTokens;
        sort($sortedSource);
        sort($sortedIncoming);
        if ($sortedSource === $sortedIncoming) {
            return true;
        }

        if (abs(count($sourceTokens) - count($incomingTokens)) > 1) {
            return false;
        }

        $usedIncoming = [];
        $unmatchedSource = 0;
        foreach ($sourceTokens as $sourceToken) {
            $bestIndex = null;
            foreach ($incomingTokens as $index => $incomingToken) {
                if (isset($usedIncoming[$index])) {
                    continue;
                }
                if ($this->isMinorTokenVariation($sourceToken, $incomingToken)) {
                    $bestIndex = $index;
                    break;
                }
            }

            if ($bestIndex === null) {
                $unmatchedSource++;
                continue;
            }

            $usedIncoming[$bestIndex] = true;
        }

        $unmatchedIncoming = count($incomingTokens) - count($usedIncoming);
        return $unmatchedSource <= 1 && $unmatchedIncoming <= 1;
    }

    private function isMinorTokenVariation(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $maxLength = max(strlen($a), strlen($b));
        $threshold = $maxLength <= 5 ? 1 : 2;
        if (levenshtein($a, $b) <= $threshold) {
            return true;
        }

        if ($maxLength >= 4 && metaphone($a) !== '' && metaphone($a) === metaphone($b)) {
            return true;
        }

        return false;
    }

    private function normalizeNameTokens(string $name): array
    {
        $normalized = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z\s]/', ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }

    private function normalizeGender(string $gender): ?string
    {
        $gender = strtolower(trim($gender));
        if ($gender === '') {
            return null;
        }

        if (in_array($gender, ['male', 'm'], true)) {
            return 'male';
        }

        if (in_array($gender, ['female', 'f'], true)) {
            return 'female';
        }

        return null;
    }

    private function extractNationality(array $person): ?string
    {
        $rawNationality = $person['nationality']
            ?? $person['citizenship']
            ?? data_get($person, 'country.nationality')
            ?? null;

        if (! is_string($rawNationality) || trim($rawNationality) === '') {
            return null;
        }

        return strtolower(trim($rawNationality));
    }

    private function attemptBaseQuery(User $user)
    {
        $query = GhanaCardVerification::query()->where('user_id', $user->id);
        if ($user->verification_attempts_reset_at) {
            $query->where('created_at', '>=', $user->verification_attempts_reset_at);
        }

        return $query;
    }

    private function extractNameParts(array $person, User $user): array
    {
        $previousName = trim((string) ($person['previousName'] ?? ''));
        $firstName = trim((string) ($person['firstName'] ?? ''));
        $middleName = trim((string) ($person['middleName'] ?? ''));
        $lastName = trim((string) ($person['surname'] ?? $person['lastName'] ?? ''));

        if ($firstName === '' && $lastName === '') {
            $fullName = trim((string) ($person['fullName'] ?? $person['forenames'] ?? ''));
            if ($fullName !== '') {
                $tokens = preg_split('/\s+/', $fullName) ?: [];
                $firstName = $tokens[0] ?? '';
                $lastName = count($tokens) > 1 ? (string) end($tokens) : ($user->last_name ?? '');
                $middleTokens = count($tokens) > 2 ? array_slice($tokens, 1, -1) : [];
                $middleName = trim(implode(' ', $middleTokens));
            }
        }

        if ($firstName === '' && ! empty($person['forenames'])) {
            $forenameTokens = preg_split('/\s+/', trim((string) $person['forenames'])) ?: [];
            $firstName = $forenameTokens[0] ?? '';
            if ($middleName === '' && count($forenameTokens) > 1) {
                $middleName = implode(' ', array_slice($forenameTokens, 1));
            }
        }

        $computedName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));

        return [
            'name' => $computedName !== '' ? $computedName : $user->name,
            'first_name' => $firstName,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'last_name' => $lastName,
            'previous_name' => $previousName !== '' ? $previousName : null,
        ];
    }

    private function storeVerifiedProfileImage(string $studentId, string $imageData, string $extension): array
    {
        $enabled = (bool) AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_ENABLED', true);
        if (! $enabled) {
            return [
                'disk' => self::DEFAULT_IMAGE_DISK,
                'path' => '',
                'url' => '',
            ];
        }

        $timeout = (int) AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_TIMEOUT_SECONDS', 15);
        $uploadUrl = (string) AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_UPLOAD_URL', '');
        $disk = (string) AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_STORAGE_DISK', self::DEFAULT_IMAGE_DISK);
        $directory = trim((string) AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_STORAGE_DIR', self::DEFAULT_IMAGE_DIR), '/');
        $filename = $studentId . '.' . strtolower($extension);
        $path = $directory . '/' . $filename;

        if ($uploadUrl !== '') {
            try {
                $response = Http::timeout($timeout)
                    ->attach('file', $imageData, $filename)
                    ->post($uploadUrl, [
                        'student_id' => $studentId,
                        'path' => $path,
                    ]);

                if ($response->successful()) {
                    $payload = (array) $response->json();
                    return [
                        'disk' => $disk,
                        'path' => (string) ($payload['path'] ?? $path),
                        'url' => (string) ($payload['url'] ?? ''),
                    ];
                }
            } catch (\Throwable $exception) {
                Log::warning('External profile image upload failed, using fallback storage.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Storage::disk($disk)->put($path, $imageData);
        $url = method_exists(Storage::disk($disk), 'url') ? (string) Storage::disk($disk)->url($path) : '';

        return [
            'disk' => $disk,
            'path' => $path,
            'url' => $url,
        ];
    }

    private function persistImageMetadata(User $user, array $storage): void
    {
        $data = $user->data ?? [];
        $data['verified_profile_image_path'] = $storage['path'];
        $data['verified_profile_image_disk'] = $storage['disk'];
        if (! empty($storage['url'])) {
            $data['verified_profile_image_url'] = $storage['url'];
        }

        $user->is_nia_syncing = true;
        $user->data = $data;
        $user->saveQuietly();
        $user->is_nia_syncing = false;
        $user->saveQuietly();
    }

    private function resolveProfileImageInfo(User $user): array
    {
        $data = $user->data ?? [];
        $disk = (string) data_get($data, 'verified_profile_image_disk', AppConfig::getValue('VERIFICATION_PROFILE_IMAGE_STORAGE_DISK', self::DEFAULT_IMAGE_DISK));
        $path = (string) data_get($data, 'verified_profile_image_path', '');
        $url = (string) data_get($data, 'verified_profile_image_url', '');

        if ($path === '') {
            $studentId = $user->student_id ?? $user->userId;
            $legacyPath = 'verified-student-images/' . $studentId . '.png';
            if (Storage::disk(self::DEFAULT_IMAGE_DISK)->exists($legacyPath)) {
                $disk = self::DEFAULT_IMAGE_DISK;
                $path = $legacyPath;
            }
        }

        if ($url === '' && $path !== '' && method_exists(Storage::disk($disk), 'url')) {
            try {
                $url = (string) Storage::disk($disk)->url($path);
            } catch (\Throwable) {
                $url = '';
            }
        }

        return [
            'available' => $path !== '',
            'url' => $url,
            'storage_disk' => $disk,
            'storage_path' => $path,
        ];
    }
}
