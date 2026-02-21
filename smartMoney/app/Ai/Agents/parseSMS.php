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
use Stringable;

#[Model('gpt-5-mini')]

class parseSMS implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;
    public $default_categories = ['Utilities', 'Cafe', 'Online Shopping', 'Groceries', 'Entertainment', 'Transportation', 'Healthcare', 'Education', 'Dining', 'Travel', 'Personal Care', 'Subscriptions'];

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a bank SMS transaction parser.';
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

        $category_description = "Required. do your best to guess the category from OtherAccountName/merchant for payment, and from transfer/bill type for transfer/payment. Examples: Utilities, Cafe, Online Shopping.%%categories%%Use 'Unknown' if cannot infer or error != ''";
        if($this->default_categories){
            $category_description = str_replace('%%categories%%', '(Prefer:' . implode(', ', $this->default_categories) . '),otherwise suggest another category string.', $category_description);
        }else{
            $category_description = str_replace('%%categories%%', '', $category_description);
        }

        $data = [
            'error' => $schema->string()->required()->enum(['', 'Cannot parse'])->description("Empty string means success. If failed parsing, must be 'Cannot parse'. if SMS related to password or OTP or other non-transactional SMS, use 'Invalid Transaction' and set all other fields to '' or 0 as appropriate."),
            'transactionType' => $schema->string()->required()->enum(['withdrawal', 'deposit', 'payment', 'transfer', 'unknown'])->description("withdrawal=ATM cash, deposit=incoming, payment=POS/online/card purchase/bill payment, transfer=bank transfer out or in"),
            'amount' => $schema->number()->required()->description("Transaction amount as a float. Use 0 when error != ''."),
            'currency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("3-letter currency code, uppercase. Use empty string when error != '' or not present."),
            'totalAmount' => $schema->number()->required()->description("if the total amount mentioned explicitly, ignore the account balance if mentioned in the SMS, only add if total transaction amount mentioned, add the total amount as a float. Use 0 when error != '' or total amount not mentioned in the transaction."),
            'totalAmountCurrency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("if the total amount mentioned explicitly, add the total amount currency as a 3-letter code, uppercase. Use empty string when error != '' or not present."),
            'fees' => $schema->number()->required()->description("Optional fees amount as a float. Use 0 when error != '' or if not present."),
            'feesCurrency' => $schema->string()->required()->pattern("^[A-Z]{3}$")->description("Optional fees currency as 3-letter uppercase. feesCurrency must be '' when fees = 0. Use empty string when error != '' or not present. Do not invent any currency."),
            'transactionDateTime' => $schema->string()->required()->pattern("^$|^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:Z|[\\+\\-]\\d{2}:\\d{2})$")->description($transactionDateTime_description),
            'regularExp' => $schema->string()->required()->description("Required. PHP regex with named groups to extract at least: amount, currency, MyAccountNumber, OtherAccountName, OtherAccountNumber, fees, feesCurrency, transactionDateTime when possible. Use '' when error != '' or cannot produce."),
            'category' => $schema->string()->required()->description($category_description),
            'MyAccountNumber' => $schema->string()->required()->description("My account/card identifier from the SMS (examples: X7001, XXX7001, **9010, ***3021, *2398*343, 3209332). Use '' if not present or error != ''."),
            'OtherAccountName' => $schema->string()->required()->description("For payment/transfer: receiver or merchant name. For deposit: sender name. Remove branch numbers if they look like codes (ALDREES 239 -> ALDREES) or 'S121 TAMIMI' -> 'TAMIMI'. Use '' if not present or error != ''."),
            'OtherAccountNumber' => $schema->string()->required()->description("For transfer: receiver account number. For deposit: sender account number. Use '' if not present or error != ''."), 


        ];
        return $data;
        // [
            // 'value' => $schema->string()->required(),
        // ];
    }
}
