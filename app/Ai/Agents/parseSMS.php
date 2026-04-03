<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Promptable;
use App\Models\Setting;
use Stringable;

#[Model('gpt-5-mini')]

class parseSMS implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public bool $includeRegularExp = true;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $defaultPrompt = <<<'PROMPT'
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
PROMPT;

        return Setting::get('parsesms_prompt', $defaultPrompt);
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        $timezone = config('app.timezone');

        $transactionDateTime_description = "Required. ISO 8601 datetime with timezone offset (example: 2026-02-01T00:00:00+00:00). Parse from SMS date/time. If only 'MM-DD HH:mm' is present, assume current year in ".$timezone.". If cannot parse or error != '', use empty string.";

        $data = [
            'error' => $schema->string()->required()->enum(['', 'Cannot parse'])->description("Empty string means success. If failed parsing, must be 'Cannot parse'. if SMS related to password or OTP or other non-transactional SMS, use 'Cannot parse' and set all other fields to '' or 0 as appropriate."),
            'transactionType' => $schema->string()->required()->enum(['withdrawal', 'deposit', 'payment', 'transfer', 'unknown'])->description("withdrawal=ATM cash, deposit=incoming, payment=POS/online/card purchase/bill payment, transfer=bank transfer out or in"),
            'amount' => $schema->number()->required()->description("Transaction amount as a float. Use 0 when error != ''."),
            'currency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("3-letter currency code, uppercase. Use empty string when error != '' or not present."),
            'totalAmount' => $schema->number()->required()->description("if the total amount mentioned explicitly, ignore the account balance if mentioned in the SMS, only add if total transaction amount mentioned, add the total amount as a float. Use 0 when error != '' or total amount not mentioned in the transaction."),
            'totalAmountCurrency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("if the total amount mentioned explicitly, add the total amount currency as a 3-letter code, uppercase. Use empty string when error != '' or not present."),
            'fees' => $schema->number()->required()->description("Optional fees amount as a float. Use 0 when error != '' or if not present."),
            'feesCurrency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("Optional fees currency as 3-letter uppercase. feesCurrency must be '' when fees = 0. Use empty string when error != '' or not present. Do not invent any currency."),
            'transactionDateTime' => $schema->string()->required()->pattern("^$|^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:Z|[\\+\\-]\\d{2}:\\d{2})$")->description($transactionDateTime_description),
            'MyAccountNumber' => $schema->string()->required()->description("My account/card identifier from the SMS (examples: X7001, XXX7001, **9010, ***3021, *2398*343, 3209332). Use '' if not present or error != ''."),
            'OtherAccountName' => $schema->string()->required()->description("For payment/transfer: receiver or merchant name. For deposit: sender name. Remove branch numbers if they look like codes (ALDREES 239 -> ALDREES) or 'S121 TAMIMI' -> 'TAMIMI'. Use '' if not present or error != ''."),
            'OtherAccountNumber' => $schema->string()->required()->description("For transfer: receiver account number. For deposit: sender account number. Use '' if not present or error != ''."),
        ];

        if ($this->includeRegularExp) {
            $data['regularExp'] = $schema->string()->required()->description("Required. PHP regex with named groups to extract at least: amount, currency, MyAccountNumber, OtherAccountName, OtherAccountNumber, fees, feesCurrency, transactionDateTime when possible. Use '' when error != '' or cannot produce.");
        }

        return $data;
    }
}
