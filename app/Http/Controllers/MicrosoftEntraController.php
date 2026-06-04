<?php

namespace App\Http\Controllers;

use App\Exceptions\MicrosoftEntraAuthenticationException;
use App\Models\User;
use App\Support\MicrosoftEntra\MicrosoftEntraClient;
use App\Support\MicrosoftEntra\MicrosoftEntraTokenValidator;
use App\Support\MicrosoftEntra\MicrosoftEntraUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MicrosoftEntraController extends Controller
{
    private const FLOW_SESSION_KEY = 'microsoft_entra.authorization_flow';

    private const FLOW_TTL_SECONDS = 600;

    public function redirectToProvider(
        Request $request,
        string $country,
        MicrosoftEntraClient $client,
    ): RedirectResponse {
        $country = $this->normalizeCountry($country);

        try {
            $client->assertConfigured();

            $state = $this->randomUrlSafe(32);
            $nonce = $this->randomUrlSafe(32);
            $codeVerifier = $this->randomUrlSafe(64);
            $codeChallenge = $this->base64Url(hash('sha256', $codeVerifier, true));

            $request->session()->put(self::FLOW_SESSION_KEY, [
                'state' => $state,
                'nonce' => $nonce,
                'code_verifier' => $codeVerifier,
                'country' => $country,
                'started_at' => now()->timestamp,
            ]);

            return redirect()->away($client->authorizationUrl($state, $nonce, $codeChallenge));
        } catch (MicrosoftEntraAuthenticationException $exception) {
            return $this->loginError($country, $exception->translationKey);
        }
    }

    public function callback(
        Request $request,
        MicrosoftEntraClient $client,
        MicrosoftEntraTokenValidator $validator,
        MicrosoftEntraUserResolver $resolver,
    ): RedirectResponse {
        $flow = $request->session()->pull(self::FLOW_SESSION_KEY);
        $country = $this->normalizeCountry(is_array($flow) ? ($flow['country'] ?? 'af') : 'af');

        try {
            $client->assertConfigured();
            $this->validateFlow($request, $flow);

            if ($request->filled('error')) {
                Log::notice('Microsoft Entra authorization was not completed.', [
                    'error' => $request->string('error')->toString(),
                ]);

                throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.authorization_failed');
            }

            $code = $request->string('code')->toString();

            if ($code === '') {
                throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.authorization_failed');
            }

            $tokens = $client->exchangeAuthorizationCode($code, (string) $flow['code_verifier']);
            $metadata = $client->openIdConfiguration();
            $claims = $validator->validate(
                (string) $tokens['id_token'],
                (string) $flow['nonce'],
                $metadata,
                $client->jwks($metadata),
            );
            $profile = $client->profile((string) $tokens['access_token']);
            $user = $resolver->resolve($claims, $profile);

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('microsoft_entra_identity', [
                'tenant_id' => $claims['tid'],
                'object_id' => $claims['oid'],
            ]);

            return redirect()->intended('/admin/'.$this->destinationCountry($user, $country));
        } catch (MicrosoftEntraAuthenticationException $exception) {
            return $this->loginError($country, $exception->translationKey);
        } catch (Throwable $exception) {
            Log::error('Unexpected Microsoft Entra authentication failure.', [
                'exception' => $exception::class,
            ]);

            return $this->loginError($country, 'aho.microsoft_entra.errors.generic');
        }
    }

    /**
     * @param  array<string, mixed>|mixed  $flow
     */
    private function validateFlow(Request $request, mixed $flow): void
    {
        $state = $request->string('state')->toString();
        $startedAt = is_array($flow) ? (int) ($flow['started_at'] ?? 0) : 0;

        if (
            ! is_array($flow)
            || blank($flow['state'] ?? null)
            || blank($flow['nonce'] ?? null)
            || blank($flow['code_verifier'] ?? null)
            || $state === ''
            || ! hash_equals((string) $flow['state'], $state)
            || $startedAt < now()->timestamp - self::FLOW_TTL_SECONDS
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.state_invalid');
        }
    }

    private function loginError(string $country, string $translationKey): RedirectResponse
    {
        return redirect()
            ->route('filament.admin.auth.login', ['country' => $this->normalizeCountry($country)])
            ->withErrors(['microsoft_entra' => __($translationKey)]);
    }

    private function destinationCountry(User $user, string $fallback): string
    {
        if ($user->is_super_admin) {
            return 'af';
        }

        $iso = optional($user->location)->iso_alpha;

        return filled($iso)
            ? strtolower(Str::substr(trim((string) $iso), 0, 2))
            : $this->normalizeCountry($fallback);
    }

    private function normalizeCountry(mixed $country): string
    {
        $country = strtolower(trim((string) $country));

        return preg_match('/^(af|[a-z]{2})$/', $country) === 1 ? $country : 'af';
    }

    private function randomUrlSafe(int $bytes): string
    {
        return $this->base64Url(random_bytes($bytes));
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
