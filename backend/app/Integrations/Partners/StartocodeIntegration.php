<?php

namespace App\Integrations\Partners;

use App\Models\Partner;
use App\Models\User;
use App\Models\Programme;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StartocodeIntegration extends AbstractPartnerIntegration
{

    public function __construct(Partner $partner)
    {
        parent::__construct($partner);
        Http::record(function (Request $request, Response $response) {
            // Log the request URL and method
            Log::info("Request sent to: " . $request->url());
        });
    }

    /**
     * The Startocode API base URL from credentials.
     * e.g. https://startocode.com/api/v2/partners/gh/integration
     */
    protected function getBaseUrlAndHeaders(): array
    {
        return [
            'base_url' => rtrim($this->credentials['base_url'] ?? 'https://startocode.com/api/v2/partners/gh/integration', '/'),
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $this->credentials['headers'] ?? []),
        ];
    }

    /**
     * Admit a student to Startocode.
     */
    public function admitStudent(User $user, Programme $programme): bool | array
    {
        $requestConfig = $this->getBaseUrlAndHeaders();

        // Startocode requires a learning_path_id. 
        // We assume it's stored in the programme's meta or a specific field.
        // For now, we'll look for 'startocode_learning_path_id' in credentials or programme meta.
        $learningPathId = $programme->meta['external_id'] ?? null;

        if (!$learningPathId) {
            $this->log("Missing external_id for programme {$programme->id}", 'error');
            return false;
        }

        $response = Http::withHeaders($requestConfig['headers'])->timeout(10)->post("{$requestConfig['base_url']}/register", [
            'email' => $user->email,
            'password' => Str::random(15),
            'learning_path_id' => $learningPathId,
            'omcp_id' => $user->student_id,
        ]);

        if ($response->successful() && $response->json('status') === 'success') {
            $data = $response->json();
            $this->log("Admitted student {$user->student_id} successfully", 'info', ['data' => $data]);
            $user->partnerAdmissions()->updateOrCreate([
                'partner_id' => $this->partner->id,
                'programme_id' => $programme->id,
            ], [
                'external_user_id' => $data['user_id'],
                'enrollment_status' => 'enrolled',
                'meta' => $data,
            ]);
            $data['success'] = true;
            $data['external_user_id'] = $data['partner_student_ref'];
            return $data;
        }

        $this->log("Failed to admit student {$user->student_id}", 'error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return false;
    }

    /**
     * Get the SSO login URL for the student.
     */
    public function loginStudent(User $user): string
    {
        $requestConfig = $this->getBaseUrlAndHeaders();
        $data = [
            'email' => $user->email,
            'omcp_id' => $user->student_id ?? $user->userId,
        ];

        $response = Http::withHeaders($requestConfig['headers'])->post("{$requestConfig['base_url']}/login", $data);

        if ($response->successful()) {
            return $response->json('data.redirect_url');
        }

        $this->log("Failed to get login URL for student {$user->student_id}", 'error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        throw new Exception("Could not generate Startocode SSO link.");
    }

    /**
     * Get student progress.
     */
    public function getStudentProgress(User $user, Programme $programme): array
    {
        $requestConfig = $this->getBaseUrlAndHeaders();

        $response = Http::withHeaders($requestConfig['headers'])->get("{$requestConfig['base_url']}/progress/{$user->student_id}");

        if ($response->successful()) {
            $data = $response->json();
            // Map Startocode response to our interface format
            return [
                'percentage' => $data['video_percentage_complete'] ?? 0, // Or weighted avg
                'last_activity' => isset($data['last_activity_at']) ? new \DateTime($data['last_activity_at']) : null,
                'raw' => $data
            ];
        }

        return ['percentage' => 0];
    }

    /**
     * Bulk progress for a programme.
     */
    public function getProgrammeProgress(Programme $programme): array
    {
        $requestConfig = $this->getBaseUrlAndHeaders();
        $slug = $programme->meta['startocode_program_slug'] ?? null;

        if (!$slug) {
            return [];
        }

        $response = Http::withHeaders($requestConfig['headers'])->get("{$requestConfig['base_url']}/progress/programs/{$slug}");

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Get course materials link (via SSO).
     */
    public function getCourseMaterialsLink(Programme $programme): string
    {
        // For Startocode, they access materials through their dashboard after SSO.
        // We can just return the login URL logic if we have the user, 
        // but this interface method doesn't take a user.
        // We might need to adjust the interface or return a generic link.
        return $this->credentials['materials_url'] ?? 'https://startocode.com/dashboard';
    }

    public function getExternalUserId(): string
    {
        return '';
    }
}
