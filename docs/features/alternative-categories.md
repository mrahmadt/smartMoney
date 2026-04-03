# Alternative Categories

Alternative categories allow merchants to have multiple plausible spending categories. When a transaction is created for a merchant with alternatives, users can review and reassign it through a dedicated UI.

## How It Works

### Setting Up Alternatives

1. Navigate to **Category Mappings** in the admin panel
2. Edit a merchant mapping (e.g., "ALDREES")
3. The form shows:
   - **Category** (primary): The default category (e.g., "Transportation")
   - **Alternative Categories** (multi-select): Additional plausible categories (e.g., "Auto & Vehicle", "Fuel")
4. Click the **sparkle icon** ("Suggest Alternatives") to have AI suggest alternatives based on the merchant name and existing category mappings

### AI Suggestions

The `SuggestAlternativeCategories` agent:
- Takes the current category, store name, and up to 10 example stores in the same category
- Suggests 1-4 alternative categories from the existing category list
- Suggestions appear as selectable chips — click to add to the alternatives field
- You decide which suggestions to keep before saving

### Transaction Review Flow

When a new transaction is created for a merchant that has alternative categories:

1. A `PendingCategoryReview` record is created automatically during `parseSMSJob`
2. The review stores a **snapshot** of the alternatives at creation time (changes to mappings don't affect existing reviews)
3. The review appears on the **Review Categories** page and the **Dashboard widget**

### Review Categories Page

The dedicated review page shows a table with:
- **Date, Store, Amount** — transaction details
- **Current Category** — the category assigned at creation (badge)
- **Alternative Categories** — clickable buttons for each alternative
- **Dismiss** — remove from the review list without changes

**Actions:**
- **Click alternative category:** Updates the transaction in Firefly III with the new category. Row disappears immediately.
- **Dismiss (X):** Marks as dismissed. No changes to the transaction.

### Dashboard Widget

Shows the latest 5 pending reviews with:
- Store name, date, amount, current category
- Alternative category buttons (same click-to-apply behavior)
- Dismiss button
- "View All" link to the full review page

A **navigation badge** on "Review Categories" shows the pending count.

### Changing Default Category

The "Set as default" action (available in the code, can be enabled in the UI):
- Updates the Firefly III transaction with the new category
- Swaps the CategoryMapping: old default moves to alternatives, selected alternative becomes the new default
- Only affects future transactions — existing transactions keep their current category

### Access Control

- **Admin (user_id=1):** Sees all pending reviews
- **Other users:** See reviews for their own accounts, plus accounts sharing their budget (if `budget_id` is set)

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/CategoryMapping.php` | `alternative_category_ids` JSON field |
| `app/Models/PendingCategoryReview.php` | Review tracking model |
| `app/Ai/Agents/SuggestAlternativeCategories.php` | AI suggestion agent |
| `app/Filament/Pages/ReviewTransactions.php` | Review page with inline actions |
| `app/Filament/Widgets/PendingCategoryReviewsWidget.php` | Dashboard widget |
| `app/Filament/Resources/CategoryMappings/CategoryMappingResource.php` | Edit form with AI suggest button |
| `app/Jobs/parseSMSJob.php` | Creates pending reviews after transaction |

## Database

### `category_mappings` table
- `alternative_category_ids` — JSON array of category IDs (nullable)

### `pending_category_reviews` table
- `firefly_transaction_id` — Firefly III transaction ID
- `account_name` — merchant name
- `current_category_id` — category at creation time
- `alternative_category_ids` — snapshot of alternatives
- `user_id`, `budget_id` — for access control
- `transaction_amount`, `currency_code`, `transaction_date`, `transaction_description` — for display
- `status` — `pending` or `dismissed`
