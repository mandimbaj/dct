<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserCountryAccess;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MicrosoftEntraAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT_ID = '11111111-1111-4111-8111-111111111111';

    private const CLIENT_ID = '22222222-2222-4222-8222-222222222222';

    private const OBJECT_ID = '33333333-3333-4333-8333-333333333333';

    public function test_login_page_displays_microsoft_sign_in_button_when_enabled(): void
    {
        $this->configureMicrosoftEntra();

        $this->get('/admin/sn/login')
            ->assertOk()
            ->assertSee('Sign in with Microsoft');
    }

    public function test_incomplete_configuration_returns_a_safe_login_error(): void
    {
        $this->configureMicrosoftEntra();
        config(['services.microsoft_entra.client_id' => '']);

        $this->get('/admin/sn/microsoft/login')
            ->assertRedirect(route('filament.admin.auth.login', ['country' => 'sn']))
            ->assertSessionHasErrors([
                'microsoft_entra' => 'Microsoft sign-in is not fully configured. Please contact an administrator.',
            ]);
    }

    public function test_redirect_starts_tenant_specific_authorization_code_flow_with_pkce(): void
    {
        $this->configureMicrosoftEntra();

        $response = $this->get('/admin/sn/microsoft/login');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith(
            'https://login.microsoftonline.com/'.self::TENANT_ID.'/oauth2/v2.0/authorize?',
            $location,
        );

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('query', $query['response_mode']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['state']);
        $this->assertNotEmpty($query['nonce']);
        $this->assertNotEmpty($query['code_challenge']);

        $response->assertSessionHas('microsoft_entra.authorization_flow', function (array $flow) use ($query): bool {
            return $flow['state'] === $query['state']
                && $flow['nonce'] === $query['nonce']
                && $flow['country'] === 'sn'
                && filled($flow['code_verifier']);
        });
    }

    public function test_callback_creates_and_authenticates_user_without_local_access_rights(): void
    {
        $this->configureMicrosoftEntra();

        [$privateKey, $jwks] = $this->rsaKeyPair();
        $state = Str::random(40);
        $nonce = Str::random(40);
        $issuer = 'https://login.microsoftonline.com/'.self::TENANT_ID.'/v2.0';
        $metadataUrl = 'https://login.microsoftonline.com/'.self::TENANT_ID.'/v2.0/.well-known/openid-configuration';
        $jwksUrl = 'https://login.microsoftonline.com/'.self::TENANT_ID.'/discovery/v2.0/keys';
        $tokenUrl = 'https://login.microsoftonline.com/'.self::TENANT_ID.'/oauth2/v2.0/token';

        $idToken = JWT::encode([
            'aud' => self::CLIENT_ID,
            'iss' => $issuer,
            'iat' => now()->timestamp,
            'nbf' => now()->subSecond()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
            'nonce' => $nonce,
            'tid' => self::TENANT_ID,
            'oid' => self::OBJECT_ID,
            'ver' => '2.0',
            'name' => 'WHO Test User',
            'preferred_username' => 'who.user@who.int',
        ], $privateKey, 'RS256', 'test-key');

        Http::fake([
            $tokenUrl => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'graph-access-token',
                'id_token' => $idToken,
            ]),
            $metadataUrl => Http::response([
                'issuer' => $issuer,
                'jwks_uri' => $jwksUrl,
            ]),
            $jwksUrl => Http::response($jwks),
            'https://graph.microsoft.com/v1.0/me*' => Http::response([
                'id' => self::OBJECT_ID,
                'displayName' => 'WHO Test User',
                'givenName' => 'WHO',
                'surname' => 'User',
                'mail' => 'who.user@who.int',
                'userPrincipalName' => 'who.user@who.int',
            ]),
        ]);

        $response = $this
            ->withSession([
                'microsoft_entra.authorization_flow' => [
                    'state' => $state,
                    'nonce' => $nonce,
                    'code_verifier' => 'test-code-verifier',
                    'country' => 'sn',
                    'started_at' => now()->timestamp,
                ],
            ])
            ->get('/auth/microsoft/callback?'.http_build_query([
                'state' => $state,
                'code' => 'authorization-code',
            ]));

        $response->assertRedirect('/admin/sn');
        $this->assertAuthenticated();

        $user = User::query()->sole();

        $this->assertSame('WHO Test User', $user->name);
        $this->assertSame('who.user@who.int', $user->email);
        $this->assertSame(self::TENANT_ID, $user->entra_tenant_id);
        $this->assertSame(self::OBJECT_ID, $user->entra_object_id);
        $this->assertSame('who.user@who.int', $user->entra_user_principal_name);
        $this->assertNotNull($user->entra_last_login_at);
        $this->assertFalse($user->is_super_admin);
        $this->assertNull($user->location_id);
        $this->assertNull($user->role_id);
        $this->assertSame([], $user->effectivePermissions()['view']);

        Http::assertSent(function ($request) use ($tokenUrl): bool {
            return $request->url() === $tokenUrl
                && $request['code'] === 'authorization-code'
                && $request['code_verifier'] === 'test-code-verifier';
        });
    }

    public function test_user_without_country_is_not_treated_as_regional_user(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'location_id' => null,
        ]);

        $this->actingAs($user);

        $this->assertFalse(UserCountryAccess::canViewRegionalDashboard());
    }

    private function configureMicrosoftEntra(): void
    {
        config([
            'services.microsoft_entra.enabled' => true,
            'services.microsoft_entra.tenant' => self::TENANT_ID,
            'services.microsoft_entra.client_id' => self::CLIENT_ID,
            'services.microsoft_entra.client_secret' => 'test-client-secret',
            'services.microsoft_entra.redirect_uri' => 'http://localhost/auth/microsoft/callback',
            'services.microsoft_entra.scopes' => ['openid', 'profile', 'email', 'User.Read'],
            'services.microsoft_entra.local_login_enabled' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function rsaKeyPair(): array
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $laragonOpenSslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';

        if (is_file($laragonOpenSslConfig)) {
            $options['config'] = $laragonOpenSslConfig;
        }

        $resource = openssl_pkey_new($options);

        $this->assertNotFalse($resource);
        $this->assertTrue(openssl_pkey_export($resource, $privateKey, null, $options));
        $details = openssl_pkey_get_details($resource);
        $this->assertIsArray($details);

        return [
            $privateKey,
            [
                'keys' => [[
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'kid' => 'test-key',
                    'alg' => 'RS256',
                    'n' => $this->base64Url($details['rsa']['n']),
                    'e' => $this->base64Url($details['rsa']['e']),
                ]],
            ],
        ];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
