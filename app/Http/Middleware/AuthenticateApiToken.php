<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $plainToken = $request->bearerToken() ?: $request->header('X-API-Token');

        if (blank($plainToken)) {
            return $this->unauthorized('Missing API token. Use Authorization: Bearer <token>.');
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', trim((string) $plainToken)))
            ->first();

        if (! $token?->isActive() || ! $token->user) {
            return $this->unauthorized('Invalid or expired API token.');
        }

        Auth::guard('web')->setUser($token->user);
        $request->attributes->set('api_token', $token);

        try {
            $token->markUsed();
        } catch (\Throwable) {
            logger()->warning('Unable to update API token last_used_at.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
