# Firefly III Integration

SmartMoney uses Firefly III as its financial data backend. All transactions, categories, budgets, and accounts are managed in Firefly III, and SmartMoney communicates via the REST API.

## Configuration

Set in `.env`:

```env
FIREFLY_III_URL=https://your-firefly-instance/api/v1/
FIREFLY_III_TOKEN=your-personal-access-token
```

## API Operations

The `fireflyIII` service class provides a complete wrapper:

### Transactions
- **Create:** `newTransaction()` — creates withdrawal, deposit, payment, or transfer
- **Update:** `updateTransaction($id, $data)` — modify category, description, etc.
- **Fetch:** `getTransactions($start, $end, $filter, $limit, $page, $type)` — paginated with filtering
- **Search:** `searchTransactions($query)` — query-based search

### Categories
- **List:** `getCategories()` — returns all category names (cached in static property)
- **Sync:** `Category::syncFromFirefly()` — one-way sync, inserts new categories, never deletes

### Accounts
- **List:** `getAccounts($type)` — fetch by type (asset, expense, revenue)
- **Sync:** `Account::syncFromFirefly()` — syncs asset accounts, creates/updates/deletes local records
- **Create/Delete:** `createAccount()`, `deleteAccount()`

### Budgets
- **List:** `getBudgets($limit)` — all budgets with pagination
- **Get:** `getBudget($id)` — single budget with spending data

### Subscriptions (Bills)
- **Create:** `createSubscription($data)` — name, amount range, frequency, date
- **List:** `getSubscriptions($limit)` — all subscriptions
- **Find:** `findSubscription($name)` — search by name

### Rules
- **Create:** `createRule($data)` — automation rules with triggers and actions
- **Trigger:** `triggerRule($data)` — execute a rule retroactively
- **Groups:** `createRuleGroup($data)` — organize rules into groups

### Exchange Rates
- **Get:** `getExchangeRate($from, $to)` — currency conversion via Firefly III

## Multi-Currency Support

SmartMoney handles multi-currency transactions:

1. If the transaction currency differs from the account currency, both amounts are recorded
2. Fees can be in a different currency than the transaction amount
3. Exchange rates are fetched from Firefly III when currency conversion is needed
4. The `Currency` model provides utility methods for conversions

## Account Mapping

Local `Account` records map to Firefly III accounts:

| Local Field | Purpose |
|-------------|---------|
| `firefly_account_id` | Links to Firefly III asset account |
| `firefly_account_name` | Display name |
| `sender_id` | Which SMS sender (bank) this account belongs to |
| `shortcodes` | JSON array of account number fragments for matching SMS to accounts |
| `user_id` | Owner user |
| `budget_id` | Firefly III budget for transactions on this account |
| `currency_code` | Account's primary currency |

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/fireflyIII.php` | Complete API wrapper |
| `app/Models/Account.php` | Local account with `syncFromFirefly()` and `findBySenderAndShortcode()` |
| `app/Models/Category.php` | Category with `syncFromFirefly()` |
| `app/Models/Transaction.php` | `createTransaction()` with multi-currency handling |
| `app/Models/Currency.php` | Exchange rate utilities |
