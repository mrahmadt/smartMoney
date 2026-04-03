# Dashboard & Widgets

The SmartMoney dashboard provides a real-time overview of your finances through configurable widgets.

## Dashboard Layout

The dashboard uses a single-column layout with conditional widgets. Widgets appear based on user state and data availability.

## Widgets

### Header Widgets (Conditional)

These appear at the top of the dashboard when conditions are met:

| Widget | Condition | Description |
|--------|-----------|-------------|
| **WebPushPrompt** | User hasn't subscribed to push | Prompts to enable browser notifications |
| **InvalidSMSBanner** | Admin only, invalid SMS exist | Warning banner with count of unparseable SMS |
| **PinnedAlertsBanner** | User has pinned unread alerts | Shows up to 3 pinned alerts with dismiss buttons |
| **PendingCategoryReviewsWidget** | Pending reviews exist | Shows 5 most recent transactions needing category review |

### Stats Overview

Displays budget statistics for the current month:
- **Budget remaining** — shows the remaining budget amount
- **Budget spent** — total spending this month
- Color-coded: green when within budget, red when over

### Charts

| Widget | Description |
|--------|-------------|
| **SpendingCategoriesChart** | Pie chart showing top 14 spending categories for the current month |

### Tables

| Widget | Description |
|--------|-------------|
| **RecentTransactions** | Latest 5 transactions with merchant, amount, date, category. Clickable to edit. |
| **TopTransactions** | 10 largest transactions this month. Clickable to edit. |
| **TopMerchants** | Top 10 merchants by total spending this month |
| **TopCategories** | Top 5 categories by total spending this month |

## Transaction Pages

### List Transactions

Paginated table of the current month's transactions fetched from Firefly III:
- Merchant name, amount (color-coded: red for withdrawals, green for deposits), date, category
- Click any row to edit the transaction

### Edit Transactions

Form to modify transaction details in Firefly III:
- Description, type, amount, source account, destination, date
- Budget, category, tags, notes
- Saves directly to Firefly III via API

## Navigation

The sidebar includes:
- **Dashboard** — main overview
- **Transactions** — browse current month
- **Review Categories** — pending category reviews (with badge count)
- **Alerts** — notification history (with unread badge)
- **Config** group — admin-only resources (Accounts, Categories, Mappings, SMS, Settings, Users)

## Key Files

| File | Purpose |
|------|---------|
| `app/Filament/Pages/Dashboard.php` | Dashboard with conditional widget registration |
| `app/Filament/Pages/ListTransactions.php` | Transaction browser |
| `app/Filament/Pages/EditTransactions.php` | Transaction editor |
| `app/Filament/Widgets/StatsOverview.php` | Budget stats |
| `app/Filament/Widgets/SpendingCategoriesChart.php` | Category pie chart |
| `app/Filament/Widgets/RecentTransactions.php` | Latest 5 transactions |
| `app/Filament/Widgets/TopTransactions.php` | Largest 10 transactions |
| `app/Filament/Widgets/TopMerchants.php` | Top merchants table |
| `app/Filament/Widgets/TopCategories.php` | Top categories table |
| `app/Filament/Widgets/PinnedAlertsBanner.php` | Pinned alerts banner |
| `app/Filament/Widgets/PendingCategoryReviewsWidget.php` | Category review widget |
| `app/Filament/Widgets/WebPushPrompt.php` | Push notification prompt |
| `app/Filament/Widgets/InvalidSMSBanner.php` | Invalid SMS warning |
