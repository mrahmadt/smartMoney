# Multi-User Access Control

SmartMoney supports multiple users with scoped access to accounts and data based on ownership and budget assignment.

## User Model

Each user has:
- `budget_id` — optional Firefly III budget ID. Links the user to a shared budget.
- `language` — UI language preference (en/ar)
- `alert_via_email` — whether to receive email notifications
- `mfa_required` — whether MFA is enforced

## Access Control Rules

### Admin User (user_id = 1)

The admin user has unrestricted access:
- Sees **all** alerts, pending reviews, transactions, and accounts
- Can manage all configuration resources (SMS senders, regex patterns, keywords, categories, mappings, settings, users)
- Receives copies of anomaly alerts from all users

### Regular Users

Regular users see only data they own:

**Transactions:** Filtered by the user's Firefly III `budget_id`:
- The `ListTransactions` page fetches transactions matching the user's budget
- If `budget_id` is null, the user sees unfiltered transactions

**Alerts:** Filtered by `user_id`:
- Each user only sees their own alerts
- Navigation badge counts only their unread alerts

**Pending Category Reviews:** Filtered by ownership:
```sql
WHERE user_id = {auth.id}
   OR (budget_id = {auth.budget_id} AND {auth.budget_id} IS NOT NULL)
```
- User sees reviews for their own accounts
- Also sees reviews for accounts sharing their budget (family/shared accounts)

### Admin-Only Resources

These Filament resources are restricted to `user_id === 1`:
- Accounts
- Categories
- Category Mappings
- SMS messages
- SMS Regular Expressions
- SMS Senders
- Keywords
- Settings
- Users

## Account Ownership

The `Account` model links Firefly III accounts to users:

| Field | Purpose |
|-------|---------|
| `user_id` | Direct owner of the account |
| `budget_id` | Budget this account's transactions belong to |
| `sender_id` | Which SMS sender (bank) the account belongs to |
| `shortcodes` | Account number fragments for SMS matching |

When a transaction is created, the source account determines:
- Which `user_id` receives the alert
- Which `budget_id` is assigned to the transaction

## Alert Copying

`Alert::createAlertWithAdminCopy()` ensures important alerts reach both the affected user and the admin:

1. Creates the alert for the user
2. If the user is not the admin, creates a copy for user_id=1
3. Both copies are batched independently (each user gets their own notification)

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/User.php` | User model with budget_id |
| `app/Models/Account.php` | Account with user_id and budget_id |
| `app/Models/PendingCategoryReview.php` | `scopeForUser()` access control |
| `app/Models/Alert.php` | `createAlertWithAdminCopy()` |
| `app/Filament/Resources/*/Resource.php` | `canAccess()` checks for admin-only resources |
