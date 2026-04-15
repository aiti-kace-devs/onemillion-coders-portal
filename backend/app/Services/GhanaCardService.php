<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Models\GhanaCardVerification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GhanaCardService
{
    protected string $baseUrl;
    protected string $merchantKey;
    private const DEFAULT_IMAGE_DISK = 'private_cloud';
    private const DEFAULT_IMAGE_DIR = 'omcp/users-profile';

    public function __construct()
    {
        $this->baseUrl = (string) config('services.ghana_card.base_url', '');
        $this->merchantKey = (string) config('services.ghana_card.merchant_key', '');
    }

    /**
     * Process image and verify Ghana Card.
     */
    public function verify(User $user, $imageFile): GhanaCardVerification
    {
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
                'status_message' => ($data['success'] ?? false) ? 'Success' : 'API Error: ' . ($data['msg'] ?? 'Unknown Error'),
                'pin_number' => $data['data']['person']['cardId'] ?? $data['data']['person']['nationalId'] ?? $verification->pin_number,
            ]);

            // 5. Handle Business Logic based on codes
            $this->handleResponseCode($user, $verification);
        } catch (\Exception $e) {
            Log::error('Ghana Card Verification Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $verification->update([
                'status_message' => 'Exception: ' . $e->getMessage(),
                'code' => '04', // Map to Internal Error
            ]);
        }

        return $verification;
    }

    /**
     * Process image: resize/crop to 640x480 and convert to PNG.
     */
    protected function processImage($imageFile): string
    {
        // For file uploads, Intervention can take the file object or path
        $img = Image::make($imageFile);

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
            // NIA Watchlist - Block user
            $user->update(['is_verification_blocked' => true]);
            return;
        }

        if ($code === '00' && $verification->verified) {
            $person = $verification->person_data;

            if ($person) {
                // 1. Sync Personal Data
                $this->syncUserData($user, $person);

                // 2. Store NIA Face Image
                if (isset($person['biometricFeed']['face']['data'])) {
                    $this->saveNiaFaceImage($user, $person['biometricFeed']['face']['data']);
                }
            }
        }
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
        return GhanaCardVerification::query()
            ->where('user_id', $user->id)
            ->where('code', '00')
            ->where('verified', true)
            ->exists();
    }

    public function buildStatus(User $user): array
    {
        $maxAttempts = $this->maxAttempts();
        $usedAttempts = $this->failedAttempts($user);
        $latest = GhanaCardVerification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $imageInfo = $this->resolveProfileImageInfo($user);

        return [
            'verified' => $this->isVerified($user),
            'blocked' => (bool) $user->is_verification_blocked,
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

    public function failedAttempts(User $user): int
    {
        return GhanaCardVerification::query()
            ->where('user_id', $user->id)
            ->where('code', '!=', '00')
            ->count();
    }

    public function maxAttempts(): int
    {
        return (int) AppConfig::getValue(GHANA_CARD_MAX_ATTEMPTS, config('GHANA_CARD_MAX_ATTEMPTS', 5));
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
