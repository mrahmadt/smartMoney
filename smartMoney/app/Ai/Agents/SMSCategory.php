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

#[Model("gpt-5-mini")]

class SMSCategory implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;
    public $default_categories = [];
    public ?string $llm_model = null;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $defaultPrompt = <<<'PROMPT'
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
PROMPT;

        return Setting::get('category_prompt', $defaultPrompt);
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
            'confidence' => $schema->string()->required()->enum(['high', 'medium', 'low'])->description("high=well-known brand/merchant, medium=reasonable guess from context, low=uncertain or generic description."),
        ];

        return $data;
    }
}
