<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('gpt-5-mini')]

class GenerateRegex implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public string $smsText = '';

    public array $parsedFields = [];

    public function instructions(): Stringable|string
    {
        $fieldsJson = json_encode($this->parsedFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a PHP regular expression generator for bank SMS messages.

Given an SMS message and the extracted transaction data, generate a PHP regex pattern with named groups that can extract the same fields from similar SMS messages.

SMS message:
{$this->smsText}

Extracted fields (use these as expected output for your regex):
{$fieldsJson}

Requirements:
- Use PHP PCRE regex syntax
- Use named groups: (?P<amount>...), (?P<currency>...), (?P<sourceAccountNumber>...), (?P<sourceAccountName>...), (?P<destinationAccountNumber>...), (?P<destinationAccountName>...), (?P<fees>...), (?P<feesCurrency>...), (?P<transactionDateTime>...)
- Only include named groups for fields that have non-empty values in the extracted data
- The regex MUST include at minimum: amount, and at least one account identifier (sourceAccountNumber, sourceAccountName, destinationAccountNumber, or destinationAccountName)
- The regex should match the exact SMS provided
- Make the regex generic enough to match similar SMS formats (different amounts, dates, merchants)
- Use delimiters: /pattern/s
- Account numbers may be masked with * or X characters
- Amounts may include commas as thousand separators
- Return empty string if you cannot generate a valid regex

Example output (withdrawal/payment SMS — my account is the source):
{"regularExp": "/Purchase\\s+Amount[:\\s]*(?P<currency>[A-Z]{3})\\s*(?P<amount>[\\d,]+\\.\\d{2})\\s+At[:\\s]*(?P<destinationAccountName>[^\\n]+?)\\s+Account[:\\s]*(?P<sourceAccountNumber>[*\\d]+)\\s+Date[:\\s]*(?P<transactionDateTime>[\\d\\-\\s:]+)/s"}
PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'regularExp' => $schema->string()->required()->description("PHP regex with named groups. Use '' if cannot generate."),
        ];
    }
}
