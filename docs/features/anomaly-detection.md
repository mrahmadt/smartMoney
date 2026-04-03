# Spending Anomaly Detection

SmartMoney continuously monitors spending patterns and alerts you when something unusual is detected. Detection runs both in real-time (during transaction processing) and on scheduled intervals.

## Real-Time Detection

These checks run inside `parseSMSJob` immediately after a transaction is created:

### Abnormal Destination Amount

Compares the transaction amount against the historical average for the same merchant/destination.

- Looks back `average_transactions_months` months (default: 3)
- Triggers when the amount exceeds the average by more than `abnormal_threshold_percentage_destination`% (default: 20%)
- Only triggers if the difference exceeds `abnormal_threshold_percentage_destination_min` (default: 50 currency units)
- Creates a **pinned alert** with the multiplier (e.g., "This is 4x your normal spend at ALDREES")

### Unusual Category Frequency

Detects when you have an unusual number of transactions in the same category on a single day.

- Compares today's count against the daily average for that category
- Triggers when count >= `abnormal_frequency_multiplier` (default: 2x) the average
- Creates a **pinned alert** (e.g., "2 Transportation transactions today, which is unusual")

## Scheduled Detection

### Daily Spending Check (`app:DailySpendingCheck`)

**Runs:** Daily at 23:00

1. **Category daily total vs average:** For each category with spending today, compares the total against the average daily spend over `average_transactions_months` months. Alerts if over threshold.

2. **Unusual category frequency:** Same as real-time, but runs as a batch for all categories.

3. **Unusual destination frequency:** Detects repeated transactions to the same merchant in one day (e.g., "3 transactions at STARBUCKS today, average: 0.5 per day").

### Weekly Spending Check (`app:WeeklySpendingCheck`)

**Runs:** Sunday at 23:30

1. **Category weekly total vs average:** Compares this week's category spending against the weekly average.

2. **Destination weekly total vs average:** Identifies merchants where this week's total is significantly higher (e.g., "This is 3x your normal weekly spend at AMAZON").

### Monthly Spending Check (`app:MonthlySpendingCheck`)

**Runs:** Last day of month at 23:45

1. **Category monthly total vs average:** Compares this month's category spending against monthly averages.

2. **Destination monthly total vs average:** Same for individual merchants.

## Alert Topics

All anomaly alerts use the `abnormal` or `report` topic:
- **Real-time alerts** → topic: `abnormal` (pinned, visible immediately)
- **Scheduled reports** → topic: `report` (pinned, visible in alerts)

Both types are copied to the admin user (user_id=1) if the affected user is different.

## Settings

| Key | Default | Description |
|-----|---------|-------------|
| `average_transactions_months` | `3` | Months of history for calculating averages |
| `abnormal_threshold_percentage_destination` | `20` | Threshold % per destination (0 = disabled) |
| `abnormal_threshold_percentage_category` | `20` | Threshold % per category (0 = disabled) |
| `abnormal_threshold_percentage_destination_min` | `50` | Minimum absolute difference for destination alerts |
| `abnormal_threshold_percentage_category_min` | `100` | Minimum absolute difference for category alerts |
| `abnormal_frequency_multiplier` | `2` | Alert if daily count >= Nx average daily count |

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/Transaction.php` | `abnormalDestinationAmount()`, `unusualCategoryFrequency()`, `periodSpendingComparison()` |
| `app/Jobs/parseSMSJob.php` | Real-time anomaly checks |
| `app/Console/Commands/DailySpendingCheck.php` | Daily batch analysis |
| `app/Console/Commands/WeeklySpendingCheck.php` | Weekly batch analysis |
| `app/Console/Commands/MonthlySpendingCheck.php` | Monthly batch analysis |
| `app/Models/Alert.php` | `createAlertWithAdminCopy()` for dual-user alerts |
