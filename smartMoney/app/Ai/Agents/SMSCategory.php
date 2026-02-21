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

class SMSCategory implements Agent, Conversational, HasTools, HasStructuredOutput
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
        $category_description = "Required. do your best to guess the category from merchant, service, payment or bill. Examples: Utilities, Cafe, Online Shopping.%%categories%%Use 'Unknown' if cannot infer or error != ''.";
        if($this->default_categories){
            $category_description = str_replace('%%categories%%', '(Prefer:' . implode(', ', $this->default_categories) . '),otherwise suggest another category string.', $category_description);
        }else{
            $category_description = str_replace('%%categories%%', '', $category_description);
        }

        $data = [
            'error' => $schema->string()->required()->enum(['', 'Cannot parse'])->description("Empty string means success. If failed parsing, must be 'Cannot parse'."),
            'category' => $schema->string()->required()->description($category_description),
        ];

        return $data;
    }
}
