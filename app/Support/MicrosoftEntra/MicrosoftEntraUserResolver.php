<?php

namespace App\Support\MicrosoftEntra;

use App\Exceptions\MicrosoftEntraAuthenticationException;
use App\Models\User;
use App\Notifications\MessageReceived;
use App\Support\NotificationRecipients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MicrosoftEntraUserResolver
{
    /**
     * Resolve the immutable Entra identity to a local account.
     *
     * New accounts deliberately receive no country, role or permissions. A super administrator
     * must assign those local access rights after Microsoft has authenticated the person.
     *
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $profile
     */
    public function resolve(array $claims, array $profile): User
    {
        $tenantId = strtolower(trim((string) ($claims['tid'] ?? '')));
        $objectId = strtolower(trim((string) ($claims['oid'] ?? '')));
        $graphObjectId = strtolower(trim((string) ($profile['id'] ?? '')));

        if ($tenantId === '' || $objectId === '' || ! hash_equals($objectId, $graphObjectId)) {
            throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.profile_mismatch');
        }

        $email = $this->resolveEmail($claims, $profile);
        $name = $this->resolveName($claims, $profile, $email);
        $upn = $this->nullableString($profile['userPrincipalName'] ?? $claims['preferred_username'] ?? null);

        [$user, $created] = DB::transaction(function () use ($tenantId, $objectId, $email, $name, $upn): array {
            $identityUser = User::query()
                ->where('entra_tenant_id', $tenantId)
                ->where('entra_object_id', $objectId)
                ->lockForUpdate()
                ->first();

            $emailUser = User::query()
                ->whereRaw('lower(email) = ?', [strtolower($email)])
                ->lockForUpdate()
                ->first();

            if ($identityUser && $emailUser && ! $identityUser->is($emailUser)) {
                throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.identity_conflict');
            }

            $user = $identityUser ?? $emailUser;
            $created = ! $user;

            if (
                $user
                && (
                    (filled($user->entra_tenant_id) && ! hash_equals(strtolower((string) $user->entra_tenant_id), $tenantId))
                    || (filled($user->entra_object_id) && ! hash_equals(strtolower((string) $user->entra_object_id), $objectId))
                )
            ) {
                throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.identity_conflict');
            }

            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'password' => Str::random(64),
                    'is_super_admin' => false,
                    'is_country_admin' => false,
                    'location_id' => null,
                    'role_id' => null,
                    'menu_permissions' => null,
                ]);
            }

            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'entra_tenant_id' => $tenantId,
                'entra_object_id' => $objectId,
                'entra_user_principal_name' => $upn,
                'entra_last_login_at' => now(),
            ])->save();

            return [$user, $created];
        });

        if ($created) {
            $this->notifySuperAdministrators($user);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $profile
     */
    private function resolveEmail(array $claims, array $profile): string
    {
        foreach ([
            $profile['mail'] ?? null,
            $profile['userPrincipalName'] ?? null,
            $claims['email'] ?? null,
            $claims['preferred_username'] ?? null,
        ] as $candidate) {
            $email = strtolower(trim((string) $candidate));

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return Str::limit($email, 255, '');
            }
        }

        throw new MicrosoftEntraAuthenticationException('aho.microsoft_entra.errors.email_missing');
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $profile
     */
    private function resolveName(array $claims, array $profile, string $email): string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->nullableString($profile['givenName'] ?? null),
            $this->nullableString($profile['surname'] ?? null),
        ])));

        foreach ([
            $profile['displayName'] ?? null,
            $claims['name'] ?? null,
            $fullName,
            Str::before($email, '@'),
        ] as $candidate) {
            $name = trim((string) $candidate);

            if ($name !== '') {
                return Str::limit($name, 255, '');
            }
        }

        return $email;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 255, '');
    }

    private function notifySuperAdministrators(User $user): void
    {
        Notification::send(
            NotificationRecipients::forCountry(null, $user->id),
            new MessageReceived(
                __('aho.microsoft_entra.notifications.new_user_title'),
                __('aho.microsoft_entra.notifications.new_user_body', [
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
                'af',
            ),
        );
    }
}
