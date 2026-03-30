<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'category_prompt'],
            [
                'value' => <<<'PROMPT'
You are a financial transaction categorizer. Given a bank SMS notification, identify the spending category based on the merchant name, transaction description, or payment type.

Focus on the merchant/store name first. If the SMS mentions a known brand or store, use that to infer the category.

Category mapping hints:
- Cafe: coffee shops (Starbucks, Dunkin, Tim Hortons, Costa, Caribou)
- Groceries: supermarkets, grocery stores (Tamimi, Panda, Carrefour, Danube, Lulu, Othaim, BinDawood)
- Shopping: department stores, general retail (Target, Walmart, Costco, Sam's Club, SACO)
- Dining: restaurants, fast food (Burger King, McDonald's, KFC, Herfy, Kudu, Pizza Hut, Dominos, Shawarmer)
- Transportation: ride-hailing (Uber, Careem), fuel stations (Aldrees, Petromin, Naft), parking, toll fees
- Utilities: electricity (SEC), water (NWC), telecom (STC, Mobily, Zain)
- Healthcare: pharmacies (CVS, Nahdi, Al-Dawaa, Walgreens), hospitals, clinics, medical labs, dentists
- Entertainment: cinemas (AMC, VOX, Muvi), gaming, theme parks, streaming (Netflix, Spotify, Apple, Disney+, YouTube Premium), app stores
- Online Shopping: e-commerce (Amazon, Noon, Shein, AliExpress, Jarir, eXtra)
- Education: schools, universities, courses, bookstores, tutoring, training
- Travel: airlines (Saudia, flynas, Air Arabia), hotels, booking platforms (Booking.com, Airbnb), car rentals
- Personal Care: salons, barbers, spas, beauty products, perfumes
- Clothing: fashion stores (Zara, H&M, Centrepoint, Max, Nike, Adidas)
- Home & Furniture: IKEA, Home Centre, Pottery Barn, home maintenance
- Government: government fees, Absher, MOI, traffic fines, visa fees
- Insurance: car insurance, medical insurance, travel insurance
- Charity: donations, Zakah, sadaqah
- Cash: ATM withdrawals
- Transfer: bank transfers between accounts
PROMPT,
                'description' => 'Full system prompt used by the AI agent to categorize SMS transactions',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'parsesms_prompt'],
            [
                'value' => <<<'PROMPT'
You are a bank SMS transaction parser. Extract structured financial data from bank SMS notifications.

Common SMS formats you will encounter:
- POS/Card purchases: "Purchase Amount: SAR 45.00 At: STARBUCKS Account: ***7632 Date: 2024-01-24 12:40"
- Transfers: "Transfer of SAR 1,000.00 from account ***1234 to account 5678901234 on 01/15"
- ATM withdrawals: "Cash withdrawal SAR 500.00 from ATM Account: ***3021 Date: 2024-02-10"
- Deposits: "Salary deposit SAR 10,000.00 to account ***4567"
- Bill payments: "SADAD payment SAR 350.00 for STC bill Account: ***9010"
- Multi-currency: "Purchase USD 50.00 (SAR 187.50) At: AMAZON Account: ***7632"

Key extraction rules:
- Account identifiers may be masked: ***7632, **9010, X7001, *2398*343
- Amounts may include commas: 1,000.00 — extract as numeric
- If both a line-item amount and a total amount are present, extract both separately
- Fees may be listed separately from the main amount
- Merchant names often have branch codes appended (ALDREES 239, S121 TAMIMI) — extract the clean merchant name without codes
- Do not confuse account balance with transaction amount
- Non-transactional SMS (OTP, password, activation codes) should return error='Cannot parse'
PROMPT,
                'description' => 'Full system prompt used by the AI agent to parse SMS transaction details',
            ]
        );

        Setting::firstOrCreate(['key' => 'parsesms_enabled'], ['value' => 'true', 'description' => 'Enable or disable SMS parsing functionality']);
        Setting::firstOrCreate(['key' => 'parsesms_store_invalid_sms'], ['value' => 'true', 'description' => 'Store invalid SMS in DB for debugging and auditing']);
        Setting::firstOrCreate(['key' => 'parsesms_min_sms_length'], ['value' => '30', 'description' => 'Minimum SMS length to be considered a valid transaction']);
        Setting::firstOrCreate(['key' => 'parsesms_regex_enabled'], ['value' => 'true', 'description' => 'Enable SMS parsing via regex (SMSRegularExp). If false, skip regex and use AI only.']);
        Setting::firstOrCreate(['key' => 'parsesms_failback_ai'], ['value' => 'true', 'description' => 'Use AI as fallback when regex parsing fails']);
        Setting::firstOrCreate(['key' => 'parsesms_failback_detect_category_ai'], ['value' => 'true', 'description' => 'Use AI for category detection when Firefly III lookup fails']);
        Setting::firstOrCreate(['key' => 'parsesms_category_model'], ['value' => 'gpt-5-mini', 'description' => 'LLM model for AI-based category detection (null = agent default)']);
        Setting::firstOrCreate(['key' => 'parsesms_model'], ['value' => 'gpt-5-mini', 'description' => 'LLM model for AI-based SMS parsing (null = agent default)']);

        // Abnormal Transaction Detection
        Setting::firstOrCreate(['key' => 'average_transactions_months'], ['value' => '3', 'description' => 'Number of months to look back when calculating average transaction amounts']);
        Setting::firstOrCreate(['key' => 'abnormal_threshold_percentage_withdrawal'], ['value' => '0', 'description' => 'Abnormal threshold % for withdrawals (0 = disabled)']);
        Setting::firstOrCreate(['key' => 'abnormal_threshold_percentage_source'], ['value' => '20', 'description' => 'Abnormal threshold % per source account (0 = disabled)']);
        Setting::firstOrCreate(['key' => 'abnormal_threshold_percentage_destination'], ['value' => '20', 'description' => 'Abnormal threshold % per destination account (0 = disabled)']);
        Setting::firstOrCreate(['key' => 'abnormal_threshold_percentage_category'], ['value' => '20', 'description' => 'Abnormal threshold % per category (0 = disabled)']);
        Setting::firstOrCreate(['key' => 'abnormal_threshold_percentage_budget'], ['value' => '20', 'description' => 'Abnormal threshold % per budget (0 = disabled)']);

        // Subscription Detector
        Setting::firstOrCreate(['key' => 'SubscriptionDetector_enabled'], ['value' => 'true', 'description' => 'Enable or disable the subscription detector']);
        Setting::firstOrCreate(['key' => 'SubscriptionDetector_go_back_days'], ['value' => '120', 'description' => 'Number of days to look back for recurring transactions']);
        Setting::firstOrCreate(['key' => 'SubscriptionDetector_transactions_recurring_types'], ['value' => 'daily,weekly,monthly,quarterly,half-year,yearly', 'description' => 'Comma-separated list of recurring transaction types to detect']);
        Setting::firstOrCreate(['key' => 'SubscriptionDetector_min_amount'], ['value' => '10', 'description' => 'Minimum transaction amount to consider as a subscription']);
    }
}
