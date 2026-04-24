<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProtocolListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProtocolActivationController extends Controller
{
    public function __construct(
        private readonly ProtocolListService $protocolListService
    ) {
    }

    public function show(string $token): JsonResponse
    {
        $result = $this->protocolListService->beginActivation($token);
        $status = (int) ($result['http_status'] ?? 200);

        return response()->json(Arr::except($result, ['http_status']), $status);
    }

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'session_token' => ['required', 'string'],
            'national_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $result = $this->protocolListService->activateParticipant($validated, [
            'ip_address' => $request->ip(),
        ]);
        $status = (int) ($result['http_status'] ?? 200);

        return response()->json(Arr::except($result, ['http_status']), $status);
    }
}
