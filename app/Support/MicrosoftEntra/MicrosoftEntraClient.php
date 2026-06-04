<?php

namespace App\Support\MicrosoftEntra;

use App\Exceptions\MicrosoftEntraAuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MicrosoftEntraClient
{
    private const LOGIN_HOST = 'login.microsoftonline.com';

    private const GRAPH_ME_URL = 'https://graph.microsoft.com/v1.0/me';

    public function enabled(): bool
    {
        return (bool) config('services.microsoft_entra.enabled', false);
    }

    public function configured(): bool
    {
        try {
            $this->assertConfigured();

            return true;
        } catch (MicrosoftEntraAuthenticationException) {
            return false;
        }
    }

    public function assertConfigured(): void
    {
        if (! $this->enabled()) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.disabled');
        }

        $this->tenant();

        if (
            blank($this->clientId())
            || blank($this->clientSecret())
            || blank($this->redirectUri())
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.configuration_missing');
        }
    }

    public function tenant(): string
    {
        $tenant = trim((string) config('services.microsoft_entra.tenant'));
        $reservedTenants = ['common', 'organizations', 'consumers'];

        if (
            $tenant === ''
            || strlen($tenant) > 253
            || in_array(strtolower($tenant), $reservedTenants, true)
            || preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/i', $tenant) !== 1
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.configuration_missing');
        }

        return $tenant;
    }

    public function clientId(): string
    {
        return trim((string) config('services.microsoft_entra.client_id'));
    }

    public function clientSecret(): string
    {
        return trim((string) config('services.microsoft_entra.client_secret'));
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.microsoft_entra.redirect_uri'));

        return $configured !== '' ? $configured : route('microsoft-entra.callback');
    }

    /**
     * @return array<int, string>
     */
    public function scopes(): array
    {
        $scopes = (array) config('services.microsoft_entra.scopes', []);

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $scope): string => trim((string) $scope),
            $scopes,
        ))));
    }

    public function authorizationUrl(
        string $state,
        string $nonce,
        string $codeChallenge,
    ): string {
        $this->assertConfigured();

        $query = http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->tenantEndpoint('/oauth2/v2.0/authorize').'?'.$query;
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code, string $codeVerifier): array
    {
        $this->assertConfigured();

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(15)
                ->post($this->tenantEndpoint('/oauth2/v2.0/token'), [
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                    'code_verifier' => $codeVerifier,
                    'scope' => implode(' ', $this->scopes()),
                ]);
        } catch (Throwable $exception) {
            Log::warning('Microsoft Entra token request failed.', ['exception' => $exception::class]);

            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_exchange_failed', $exception);
        }

        if (! $response->successful()) {
            Log::warning('Microsoft Entra token request was rejected.', [
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_exchange_failed');
        }

        $tokens = $response->json();

        if (
            ! is_array($tokens)
            || blank($tokens['id_token'] ?? null)
            || blank($tokens['access_token'] ?? null)
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_exchange_failed');
        }

        return $tokens;
    }

    /**
     * @return array<string, mixed>
     */
    public function openIdConfiguration(): array
    {
        $this->assertConfigured();

        return Cache::remember(
            'microsoft-entra.oidc-metadata.'.sha1($this->tenant()),
            now()->addHours(6),
            function (): array {
                $metadata = $this->fetchJson(
                    $this->tenantEndpoint('/v2.0/.well-known/openid-configuration'),
                    'aho.microsoft_entra.errors.provider_unavailable',
                );

                $issuer = (string) ($metadata['issuer'] ?? '');
                $jwksUri = (string) ($metadata['jwks_uri'] ?? '');

                $this->assertMicrosoftLoginUrl($issuer);
                $this->assertMicrosoftLoginUrl($jwksUri);

                return $metadata;
            },
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function jwks(array $metadata): array
    {
        $jwksUri = (string) ($metadata['jwks_uri'] ?? '');
        $this->assertMicrosoftLoginUrl($jwksUri);

        return Cache::remember(
            'microsoft-entra.jwks.'.sha1($jwksUri),
            now()->addHours(6),
            fn (): array => $this->fetchJson($jwksUri, 'aho.microsoft_entra.errors.provider_unavailable'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(15)
                ->get(self::GRAPH_ME_URL, [
                    '$select' => 'id,displayName,givenName,surname,mail,userPrincipalName',
                ]);
        } catch (Throwable $exception) {
            Log::warning('Microsoft Graph profile request failed.', ['exception' => $exception::class]);

            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.profile_failed', $exception);
        }

        $profile = $response->json();

        if (! $response->successful() || ! is_array($profile) || blank($profile['id'] ?? null)) {
            Log::warning('Microsoft Graph profile request was rejected.', ['status' => $response->status()]);

            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.profile_failed');
        }

        return $profile;
    }

    private function tenantEndpoint(string $path): string
    {
        return 'https://'.self::LOGIN_HOST.'/'.rawurlencode($this->tenant()).$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJson(string $url, string $errorKey): array
    {
        try {
            $response = Http::acceptJson()->timeout(15)->get($url);
        } catch (Throwable $exception) {
            Log::warning('Microsoft Entra metadata request failed.', [
                'url' => $url,
                'exception' => $exception::class,
            ]);

            throw new MicrosoftEntraAuthenticationException($errorKey, $exception);
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            Log::warning('Microsoft Entra metadata request was rejected.', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            throw new MicrosoftEntraAuthenticationException($errorKey);
        }

        return $data;
    }

    private function assertMicrosoftLoginUrl(string $url): void
    {
        if (
            parse_url($url, PHP_URL_SCHEME) !== 'https'
            || strtolower((string) parse_url($url, PHP_URL_HOST)) !== self::LOGIN_HOST
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.provider_unavailable');
        }
    }
}
