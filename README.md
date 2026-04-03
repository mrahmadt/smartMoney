# SmartMoney v2

> SmartMoney v2 is currently under active development. It is a complete rebuild focused on standalone features that can operate with or without SMS parsing. Stay tuned.

SmartMoney is an intelligent personal finance automation platform that converts bank SMS notifications into structured transactions in [Firefly III](https://www.firefly-iii.org/). It combines regex-based parsing with AI/LLM assistance to extract transaction details, categorize spending, detect anomalies, and keep users informed with timely notifications.

## Key Features

| Feature | Description |
|---------|-------------|
| [SMS Parsing Pipeline](docs/features/sms-parsing-pipeline.md) | Automatically extract transaction data from bank SMS using regex patterns or AI fallback |
| [AI-Powered Categorization](docs/features/ai-categorization.md) | Categorize transactions by merchant name using AI with customizable prompts |
| [Alternative Categories](docs/features/alternative-categories.md) | Assign alternative categories to merchants and review/reassign transactions inline |
| [Spending Anomaly Detection](docs/features/anomaly-detection.md) | Daily, weekly, and monthly spending analysis with configurable thresholds |
| [Subscription Detection](docs/features/subscription-detection.md) | Automatically identify recurring transactions and create Firefly III subscriptions |
| [Alert Notification System](docs/features/alert-notifications.md) | Batched Web Push and email notifications with configurable delay |
| [Firefly III Integration](docs/features/firefly-integration.md) | Full REST API integration for transactions, categories, budgets, accounts, and rules |
| [Multi-Language Support](docs/features/multi-language.md) | English and Arabic interface with per-user language preferences |
| [Multi-Factor Authentication](docs/features/mfa.md) | TOTP-based MFA (Google Authenticator) with recovery codes, per-user enforcement |
| [Dashboard & Widgets](docs/features/dashboard-widgets.md) | Real-time spending charts, top merchants, category breakdowns, and alert banners |
| [Multi-User Access Control](docs/features/access-control.md) | Per-user account/budget scoping with admin override |
| [PWA Support](docs/features/pwa.md) | Installable progressive web app with push notifications and offline support |
| [Admin Panel](docs/features/admin-panel.md) | Full configuration UI for SMS senders, regex patterns, keywords, categories, and settings |

## Tech Stack

- **Backend:** Laravel 12, PHP 8.5
- **Admin UI:** Filament v5, Livewire 4
- **AI:** Laravel AI SDK (PrismPHP) with OpenAI (gpt-5-mini)
- **Finance Backend:** Firefly III (REST API)
- **Queue:** Laravel Queue (database driver)
- **Notifications:** Web Push (VAPID), Email
- **Database:** MySQL
- **Frontend:** Vite, Tailwind CSS
- **PWA:** Service Worker, Web App Manifest

## Architecture Overview

```
Bank SMS → API Webhook → SMSController
    → Validates sender + deduplicates
    → Dispatches parseSMSJob
        → Regex matching (SMSRegularExp)
        → AI fallback (parseSMS Agent)
        → Category detection (CategoryMapping → SMSCategory Agent)
        → Creates transaction in Firefly III
        → Creates alert → SendBatchedNotifications (delayed)
        → Creates pending category review (if alternatives exist)
        → Anomaly detection checks
```

## Getting Started

### Prerequisites

- PHP 8.2+
- MySQL
- Composer
- Node.js + npm
- Firefly III instance with API token
- OpenAI API key

### Installation

```bash
git clone <repo-url>
cd smartMoney
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Configuration

Set these in your `.env`:

```env
# Firefly III
FIREFLY_III_URL=https://your-firefly-instance/api/v1/
FIREFLY_III_TOKEN=your-api-token

# OpenAI
OPENAI_API_KEY=your-openai-key

# Web Push (generate with: php artisan webpush:vapid)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:your@email.com

# Database
DB_CONNECTION=mysql
DB_DATABASE=smartmoney
```

### Setup

```bash
php artisan migrate
php artisan db:seed
npm run build
```

### Running

```bash
# Development
composer dev

# Production
php artisan serve
php artisan queue:work
php artisan schedule:work
```

## Scheduled Tasks

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:DailySpendingCheck` | Daily 23:00 | Category and destination spending anomaly detection |
| `app:WeeklySpendingCheck` | Sunday 23:30 | Weekly spending comparison vs 3-month average |
| `app:MonthlySpendingCheck` | Last day of month 23:45 | Monthly spending analysis |
| `app:SubscriptionDetector` | Every 10 days | Identify recurring transactions |
| `app:CleanupSMS` | Daily | Delete processed SMS older than configured days |
| `app:CleanupAlerts` | Daily | Delete read alerts and old transaction alerts |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/sms/filter` | SMS webhook — receives bank SMS for processing |
| POST | `/webpush/subscribe` | Register browser for push notifications |
| DELETE | `/webpush/unsubscribe` | Unregister from push notifications |

## Configuration

SmartMoney uses a dynamic key-value settings system accessible through the admin panel. All settings are cached for 1 hour.

See [Admin Panel](docs/features/admin-panel.md) for the full settings reference.

## License

MIT


## Old README.md

## Introducing smartMoney!

smartMoney automate SMS parsing and transaction creation for FireFly-III with suite of scripts designed to enhance your experience with [FireFly-III](https://firefly-iii.org/). Developed to streamline transaction creation in FireFly-III, this project aims to simplify financial management processes.

## Key Features

## Automatic SMS Parsing and Transaction Creation
Utilize [SMSReceiver-iOS](https://github.com/mrahmadt/SMSReceiver-iOS) to seamlessly forward your bank SMS messages to smartMoney. Features include:
- SMS parsing using regular expressions
- SMS parsing using ChatGPT (Optional)
- Transaction categorization using ChatGPT or from similar transactions stored in FireFly-III (Optional)
- Create transaction in FireFly


![SMS2Transaction](https://github.com/mrahmadt/smartMoney/assets/957921/427f90ef-8daa-4bc6-8403-08d6a4855587)



## Detection of Abnormal Transactions
Identify withdrawals exceeding the average amount from expense accounts or categories.

## Automatic Subscription Detection
Detect subscriptions on various intervals (daily, weekly, monthly, etc.) and automatically create bills in FireFly-III, facilitating the linking of bills to future transactions.

## Subscription Amount Increase Alerts
Alerts for increases in subscription amounts.

## Simple Budget Web Application
Access a straightforward web application to view specific budgets and track withdrawals and deposits. Ideal for sharing accounts with others or monitoring home expenses without sharing FireFly-III admin credentials.

![IMG_2867](https://github.com/mrahmadt/smartMoney/assets/957921/9bdd2583-2cb9-4fe6-bd9c-8808c8d894fe)

## Alert Notifications
Receive push notifications on your mobile device via Web Push notification or email about transactions, bills, and budget.


### Installation Guide
Check out our [Wiki Pages](https://github.com/mrahmadt/smartMoney/wiki)