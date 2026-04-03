# Multi-Factor Authentication (MFA)

SmartMoney uses Filament v5's built-in MFA with TOTP (Time-based One-Time Password) authentication, compatible with Google Authenticator, Authy, 1Password, and other authenticator apps.

## How It Works

### Setup Flow

1. User navigates to their profile page
2. Clicks "Set up" under App Authentication
3. Scans the QR code with their authenticator app
4. Enters the 6-digit code to verify
5. Receives 8 recovery codes for backup

### Login Flow

1. User enters email and password
2. If MFA is enabled, prompted for 6-digit code from authenticator app
3. Can use a recovery code if they've lost their device (each code is single-use)

### Per-User Enforcement

MFA can be required on a per-user basis:

- **`mfa_required = false` (default):** MFA is available in the profile page but not required
- **`mfa_required = true`:** User must set up MFA before they can access the app. They'll be redirected to the setup page on login.

Admins can toggle this per user in the **Users** management page.

## Recovery Codes

- 8 codes generated on MFA setup
- Each code is single-use
- Users can regenerate codes from their profile page
- Codes are stored encrypted in the database

## Database

### `users` table columns
| Column | Type | Description |
|--------|------|-------------|
| `mfa_secret` | string, nullable | TOTP secret key (hidden from serialization) |
| `mfa_recovery_codes` | json, nullable | Array of recovery codes (hidden from serialization) |
| `mfa_required` | boolean, default false | Whether MFA is required for this user |

## Configuration

MFA is configured in `AdminPanelProvider.php`:

```php
->multiFactorAuthentication(
    providers: [
        AppAuthentication::make()->recoverable(true),
    ],
    isRequired: fn () => auth()->user()?->mfa_required ?? false,
)
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Providers/Filament/AdminPanelProvider.php` | MFA panel configuration |
| `app/Models/User.php` | Implements `HasAppAuthentication` and `HasAppAuthenticationRecovery` |
| `database/migrations/2026_04_02_000000_add_mfa_columns_to_users_table.php` | MFA columns |
| `database/migrations/2026_04_02_000001_add_mfa_required_to_users_table.php` | Per-user enforcement |
