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

class ResolveCurrency implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public string $currencyText = '';

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are a currency code resolver. Given a text that represents a currency (which may be in any language, symbol, or abbreviation), return the ISO 4217 3-letter currency code.

Text to resolve: "{$this->currencyText}"

Examples:
- "ريال" → SAR (Saudi Riyal)
- "ر.س." → SAR
- "ريال سعودي" → SAR
- "درهم" → AED (if context is UAE) or MAD (if context is Morocco) — default to AED
- "دولار" → USD
- "$" → USD
- "€" → EUR
- "£" → GBP
- "¥" → JPY

If you cannot determine the currency, return an empty string.
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
            'code' => $schema->string()->required()->description("ISO 4217 3-letter currency code, uppercase. Empty string if cannot determine."),
        ];
    }
}
