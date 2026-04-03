# Subscription Detection

SmartMoney automatically identifies recurring transactions and creates subscriptions (bills) in Firefly III.

## How It Works

The `app:SubscriptionDetector` command runs every 10 days and:

1. **Fetches transaction history:** Pulls all withdrawals from the last `SubscriptionDetector_go_back_days` (default: 120) days from Firefly III
2. **Groups by destination:** Groups transactions by merchant/destination account
3. **Detects patterns:** Analyzes the time intervals between transactions to the same destination
4. **Creates subscriptions:** For detected recurring patterns, creates a Firefly III subscription (bill) and a matching rule

## Detection Patterns

| Pattern | Interval | Min Occurrences |
|---------|----------|-----------------|
| Daily | 0-4 days apart | 7 |
| Weekly | 5-8 days apart | 3 |
| Monthly | 28-34 days apart | 3 |
| Quarterly | 80-100 days apart | 2 |
| Half-year | 170-200 days apart | 2 |
| Yearly | 350-390 days apart | 2 |

## What Gets Created

For each detected subscription:

1. **Firefly III Subscription:** With the merchant name, min/max amount range, and frequency
2. **Firefly III Rule:** Automatically links future transactions to the subscription based on destination name
3. **Rule Trigger:** The rule is triggered retroactively to link existing transactions
4. **Alert:** An alert is sent to the admin (topic: `subscription`)

## Filtering

- **Skips existing bills:** Transactions already linked to a Firefly III bill are excluded
- **Minimum amount:** Transactions below `SubscriptionDetector_min_amount` (default: 10) are ignored
- **Configurable types:** Only detects patterns listed in `SubscriptionDetector_transactions_recurring_types`

## Settings

| Key | Default | Description |
|-----|---------|-------------|
| `SubscriptionDetector_enabled` | `true` | Enable/disable detection |
| `SubscriptionDetector_go_back_days` | `120` | Days of history to analyze |
| `SubscriptionDetector_transactions_recurring_types` | `daily,weekly,monthly,quarterly,half-year,yearly` | Which patterns to detect |
| `SubscriptionDetector_min_amount` | `10` | Minimum transaction amount |

## Key Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/SubscriptionDetector.php` | Main command with `detectRecurringTransactions()` |
| `app/Services/fireflyIII.php` | `createSubscription()`, `createRule()`, `triggerRule()` |
