<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use Throwable;

class Recaptcha implements ValidationRule
{

    // 1. Define a protected property
    protected string $action;

    // 2. Assign the action via the constructor
    public function __construct(string $action = 'student_login')
    {
        $this->action = $action;
    }


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // skip recaptcha on development
        $skipRecaptcha = config('services.recaptcha.skip_recaptcha', false);
        if ($skipRecaptcha) {
            return;
        }

        $projectId = config('services.recaptcha.project_id');
        $siteKey = config('services.recaptcha.site_key');
        $apiKey = config('services.recaptcha.api_key');
        $maxRiskAnalysisScore = config('services.recaptcha.max_risk_analysis_score');

        // Enterprise Assessment Endpoint
        $url = "https://recaptchaenterprise.googleapis.com/v1/projects/{$projectId}/assessments?key={$apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'event' => [
                'token' => $value,
                'siteKey' => $siteKey,
                'expectedAction' => $this->action,
            ],
        ]);

        $data = $response->json();

        $sameAction = $data['tokenProperties']['action'] === $this->action;

        // Check if token is valid and score is sufficient (0.5+ is usually human)
        if (!$sameAction || !($data['tokenProperties']['valid'] ?? false) || ($data['riskAnalysis']['score'] ?? 0) < $maxRiskAnalysisScore) {
            $fail('The reCAPTCHA verification failed. Please try again.');
        }
    }
}
