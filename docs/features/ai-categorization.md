# AI-Powered Categorization

SmartMoney uses AI to automatically categorize transactions when a merchant is not found in the category mapping database.

## How It Works

### Category Detection Flow

1. **CategoryMapping lookup:** When a transaction is parsed, the merchant name is looked up in the `category_mappings` table (case-insensitive)
2. **Shortcut rules:** ATM withdrawals are always "Cash", bank transfers are always "Transfer"
3. **AI fallback:** If the merchant is not mapped and `parsesms_failback_detect_category_ai` is enabled, the `SMSCategory` AI agent is called
4. **Auto-mapping:** When the AI categorizes a merchant, the mapping is automatically saved to `category_mappings` — future transactions from the same merchant skip the AI entirely

### SMSCategory AI Agent

**Model:** Configurable via `parsesms_category_model` (default: `gpt-5-mini`)

**Input:** Merchant name, transaction type, and full SMS text

**Output:** Structured JSON with:
- `category` — the spending category name
- `confidence` — high/medium/low confidence level

### Built-in Category Knowledge

The agent comes with extensive category hints:

| Category | Examples |
|----------|----------|
| Cafe | Starbucks, Dunkin, Tim Hortons, Costa |
| Groceries | Tamimi, Panda, Carrefour, Danube, Lulu |
| Shopping | Target, Walmart, Costco, SACO |
| Dining | Burger King, McDonald's, KFC, Herfy |
| Transportation | Uber, Careem, Aldrees, Petromin |
| Utilities | SEC, NWC, STC, Mobily, Zain |
| Healthcare | Nahdi, Al-Dawaa, CVS, Walgreens |
| Entertainment | AMC, VOX, Netflix, Spotify |
| Online Shopping | Amazon, Noon, Shein, AliExpress |
| Education | Schools, universities, courses |
| Travel | Saudia, flynas, Booking.com, Airbnb |
| Government | Absher, MOI, traffic fines, visa fees |
| Insurance | Car, medical, travel insurance |
| Charity | Donations, Zakah, Sadaqah |

### Custom Category Prompts

Each category in Firefly III can have a custom AI prompt that provides additional context:

- **Per-category prompt:** Set via the Category admin page (`category_prompt` field)
- **Enable/disable:** Toggle `enable_prompt` per category
- **Template variable:** Use `%categories_prompts%` in the main category prompt to inject all category-specific prompts

This allows fine-tuning categorization for domain-specific merchants without modifying the AI agent code.

### System Prompt Customization

The entire categorization prompt can be customized via the `category_prompt` setting in the admin panel. The default prompt includes the category hints above, but you can replace it entirely with your own.

## Key Files

| File | Purpose |
|------|---------|
| `app/Ai/Agents/SMSCategory.php` | AI categorization agent |
| `app/Models/ParseSMS.php` | `detectCategory()` method |
| `app/Models/CategoryMapping.php` | Merchant → Category mapping |
| `app/Models/Category.php` | Category with custom prompts |

## Settings

| Key | Default | Description |
|-----|---------|-------------|
| `parsesms_failback_detect_category_ai` | `true` | Enable AI category detection |
| `parsesms_category_model` | `gpt-5-mini` | LLM model for categorization |
| `category_prompt` | (built-in) | Full system prompt for the AI agent |
