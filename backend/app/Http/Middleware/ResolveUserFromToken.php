<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserFromToken
{
    public function __construct(private readonly JwtService $jwt) {}

    /**
     * Resolve user from JWT (Bearer, token, or user_id). Set user on request; return 401 if invalid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request); // TODO: remove after tests
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json(['status' => 'error', 'message' => 'Missing token.'], 401);
        }

        $userId = $this->jwt->validate($token);
        if ($userId === null) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired token.'], 401);
        }

        $user = User::find($userId);
        if ($user === null) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 401);
        }

        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
