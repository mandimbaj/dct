# Microsoft Entra ID authentication

The Data Capture Tool can authenticate users through the WHO Microsoft Entra ID directory while
continuing to manage application roles, countries and permissions locally.

## Access model

1. Microsoft Entra ID proves that the person belongs to the configured WHO directory.
2. The application links the Microsoft identity using the immutable pair `tenant ID + object ID`.
3. On the first successful sign-in, a local user account is created with no country, role or permissions.
4. A super administrator assigns the country, role and permissions in **Authentication > Users**.

An email address is used only to link an existing local account during the first Microsoft sign-in. It
is not the durable identity key because email addresses can change.

## Security choices

- The application uses the OAuth 2.0 authorization code flow with PKCE.
- A tenant-specific authority is required. Values such as `common`, `organizations` and `consumers`
  are rejected.
- The signed OpenID Connect ID token is validated against Microsoft's published signing keys.
- The application checks the token audience, issuer, nonce, tenant ID and object ID.
- Microsoft Graph `/me` is called with delegated `User.Read` permission and its object ID must match
  the token object ID.
- Access tokens and ID tokens are never stored in the database or session.
- New directory accounts receive no local authorization rights.

## Microsoft Entra app registration

Create an app registration in the WHO Microsoft Entra admin center:

1. Choose **Accounts in this organizational directory only**.
2. Add a **Web** redirect URI for every environment:
   - Local: `http://127.0.0.1:8002/auth/microsoft/callback`
   - Production: `https://dct.afro.who.int/auth/microsoft/callback`
3. Create a client secret and store it only in the secured environment configuration.
4. Add the delegated Microsoft Graph permission `User.Read`.
5. Record the **Directory (tenant) ID**, **Application (client) ID** and client secret.

To limit access to a pre-approved subset of WHO directory users, open the Enterprise Application,
set **Assignment required?** to **Yes**, then assign the authorized users or groups. Without this
setting, any user in the configured WHO tenant can authenticate, although the application still gives
new users no role or permission.

## Environment configuration

Configure these values in `.env`:

```dotenv
SESSION_SAME_SITE=lax

MICROSOFT_ENTRA_ENABLED=true
MICROSOFT_ENTRA_TENANT=<WHO directory tenant ID>
MICROSOFT_ENTRA_CLIENT_ID=<application client ID>
MICROSOFT_ENTRA_CLIENT_SECRET=<client secret>
MICROSOFT_ENTRA_REDIRECT_URI=https://dct.afro.who.int/auth/microsoft/callback
MICROSOFT_ENTRA_SCOPES="openid profile email User.Read"
MICROSOFT_ENTRA_LOCAL_LOGIN_ENABLED=true
```

Use the tenant GUID in production when possible. A verified tenant domain such as `who.int` also
works, but never use `common`.

`SESSION_SAME_SITE=lax` is required so the browser sends the application session cookie when
Microsoft redirects the user back to the callback URL. The OAuth state and nonce checks still protect
the callback.

Run the deployment commands:

```powershell
php artisan migrate --force
php artisan optimize:clear
```

## Local login fallback

Keep `MICROSOFT_ENTRA_LOCAL_LOGIN_ENABLED=true` while the Entra app registration is being tested and
for an approved emergency administrator account. After the Microsoft flow is confirmed in production,
set it to `false` if WHO policy requires Microsoft-only sign-in.

Never commit the client secret. Rotate it according to WHO security policy and update the environment
configuration before the old secret expires.

## User administration

After a new user signs in:

1. A notification is sent to super administrators.
2. Open **Authentication > Users**.
3. Find the user by name, email or Microsoft user principal name.
4. Open the user and confirm the Microsoft Entra identity section.
5. Assign the country, role and permissions required for the person's work.
6. Ask the user to sign in again and confirm the assigned access.

The Microsoft tenant ID, object ID, user principal name and last Microsoft sign-in time are visible
to administrators but cannot be edited from the form.

## Troubleshooting

### The Microsoft button says the configuration is incomplete

Confirm that the tenant, client ID, client secret and redirect URI are set, then run
`php artisan optimize:clear`.

### Microsoft reports a redirect URI mismatch

The redirect URI in `.env` must exactly match a Web redirect URI in the app registration, including
scheme, host, port and path.

### A user can authenticate but has no menus

This is expected for a first sign-in. A super administrator must assign the user's country, role and
permissions.

### A user receives an identity conflict message

Do not manually change Entra identity columns. A super administrator should review whether the local
email account or Microsoft identity is already linked to another person.

## References

- [Microsoft identity platform authorization code flow](https://learn.microsoft.com/entra/identity-platform/v2-oauth2-auth-code-flow)
- [Microsoft identity platform ID token claims](https://learn.microsoft.com/entra/identity-platform/id-token-claims-reference)
- [Microsoft Graph `/me`](https://learn.microsoft.com/graph/api/user-get)
- [Restrict an enterprise application to assigned users](https://learn.microsoft.com/entra/identity/enterprise-apps/assign-user-or-group-access-portal)
