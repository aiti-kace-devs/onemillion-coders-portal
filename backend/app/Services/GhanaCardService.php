<?php

namespace App\Services;

use App\Models\GhanaCardVerification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class GhanaCardService
{
    protected string $baseUrl;
    protected string $merchantKey;

    public function __construct()
    {
        $this->baseUrl = config('services.ghana_card.base_url');
        $this->merchantKey = config('services.ghana_card.merchant_key');
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

            $forenames = trim($person['forenames'] ?? '');
            $parts = explode(' ', $forenames);

            $firstName = array_shift($parts);
            $middleName = implode(' ', $parts);

            $userData = [
                'first_name' => $firstName ?: $user->first_name,
                'middle_name' => $middleName ?: $user->middle_name,
                'last_name' => $person['surname'] ?? $user->last_name,
            ];

            // Update name field as well (Full Name)
            $userData['name'] = trim($userData['first_name'] . ' ' . ($userData['middle_name'] ? $userData['middle_name'] . ' ' : '') . $userData['last_name']);

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
                $path = 'verified-student-images/' . $studentId . '.png';

                \Illuminate\Support\Facades\Storage::disk('private_cloud')->put($path, $imageData);

                Log::info('Stored NIA verified image for student', ['path' => $path, 'user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store NIA face image', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
