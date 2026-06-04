<?php

namespace App\Support\MicrosoftEntra;

use App\Exceptions\MicrosoftEntraAuthenticationException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Throwable;

class MicrosoftEntraTokenValidator
{
    public function __construct(
        private readonly MicrosoftEntraClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $jwks
     * @return array<string, mixed>
     */
    public function validate(
        string $idToken,
        string $expectedNonce,
        array $metadata,
        array $jwks,
    ): array {
        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->rsaSigningKeys($jwks), 'RS256'));
        } catch (Throwable $exception) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_invalid', $exception);
        }

        $issuer = (string) ($metadata['issuer'] ?? '');
        $tokenIssuer = (string) ($claims['iss'] ?? '');
        $nonce = (string) ($claims['nonce'] ?? '');
        $tenantId = strtolower((string) ($claims['tid'] ?? ''));
        $objectId = strtolower((string) ($claims['oid'] ?? ''));
        $audience = $claims['aud'] ?? null;
        $audienceMatches = is_string($audience)
            ? hash_equals($this->client->clientId(), $audience)
            : is_array($audience) && in_array($this->client->clientId(), $audience, true);

        if (
            ! $audienceMatches
            || $issuer === ''
            || ! hash_equals($issuer, $tokenIssuer)
            || $expectedNonce === ''
            || ! hash_equals($expectedNonce, $nonce)
            || ! $this->isGuid($tenantId)
            || ! $this->isGuid($objectId)
            || (string) ($claims['ver'] ?? '') !== '2.0'
        ) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_invalid');
        }

        $configuredTenant = strtolower($this->client->tenant());

        if ($this->isGuid($configuredTenant) && ! hash_equals($configuredTenant, $tenantId)) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_invalid');
        }

        $claims['tid'] = $tenantId;
        $claims['oid'] = $objectId;

        return $claims;
    }

    /**
     * @param  array<string, mixed>  $jwks
     * @return array{keys: array<int, array<string, mixed>>}
     */
    private function rsaSigningKeys(array $jwks): array
    {
        $keys = array_values(array_filter(
            is_array($jwks['keys'] ?? null) ? $jwks['keys'] : [],
            fn (mixed $key): bool => is_array($key)
                && ($key['kty'] ?? null) === 'RSA'
                && (blank($key['alg'] ?? null) || ($key['alg'] ?? null) === 'RS256'),
        ));

        if ($keys === []) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.token_invalid');
        }

        return ['keys' => $keys];
    }

    private function isGuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        ) === 1;
    }
}
