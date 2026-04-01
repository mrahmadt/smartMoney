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

#[Model("gpt-5-mini")]

class SuggestAlternativeCategories implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public string $categoryName = '';
    public string $storeName = '';
    public array $exampleStores = [];
    public array $allCategories = [];

    public function instructions(): Stringable|string
    {
        $examples = !empty($this->exampleStores)
            ? 'Other stores in this category include: ' . implode(', ', $this->exampleStores) . '.'
            : 'No other stores are currently mapped to this category.';

        $categories = implode(', ', $this->allCategories);

        return <<<PROMPT
You are a financial transaction categorization assistant. A store named "{$this->storeName}" is currently categorized as "{$this->categoryName}". {$examples}

Suggest 1-4 alternative categories from this list that could also reasonably apply to this store: {$categories}.

Only suggest categories that make genuine sense — for example, a gas station could be "Transportation" or "Auto & Vehicle" instead of "Fuel". A supermarket could be "Shopping" instead of "Groceries". A restaurant could be "Entertainment" instead of "Dining".

Rules:
- Do NOT suggest the current category "{$this->categoryName}"
- Only pick from the provided list of categories
- If no reasonable alternatives exist, return an empty list
- Maximum 4 suggestions
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
            'categories' => $schema->array(
                $schema->string()->description('A category name from the provided list')
            )->required()->description('0-4 alternative category names. Empty array if no reasonable alternatives.'),
        ];
    }
}
