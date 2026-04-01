# Alternative Categories & Transaction Review

## Overview

Add alternative category support to CategoryMappings so stores can have multiple plausible categories. When transactions are created, users can review and reassign them to alternative categories via a dedicated UI. An AI agent suggests alternatives during mapping setup.

## Decisions

- **Architecture**: Filament-native (Livewire actions, Filament tables/widgets/pages)
- **Transaction updates**: Immediate inline — click alternative category, Firefly III updates, row removed. No confirmation dialog.
- **Default category change**: Separate explicit action per alternative. Old default moves to alternatives list.
- **Tracking**: Local `pending_category_reviews` table, populated during `parseSMSJob`
- **AI suggestions**: Presented for user selection, not auto-saved
- **Access control**: Users see own accounts + shared budget accounts. Admin (user_id=1) sees everything.

---

## 1. Data Model

### 1a. `category_mappings` — New Column

Migration: add nullable JSON column.

```
alternative_category_ids: JSON, nullable, default null
```

Stores an array of category IDs, e.g. `[3, 7, 12]`. The primary `category_id` remains the default.

**Model changes to `CategoryMapping`**:
- Add `alternative_category_ids` to `$fillable`
- Add cast: `'alternative_category_ids' => 'array'`
- Add `alternativeCategories()` method — returns Category models for stored IDs
- Add `hasAlternatives(): bool` helper

### 1b. New `pending_category_reviews` Table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-increment |
| `firefly_transaction_id` | string | Firefly III transaction ID |
| `firefly_journal_id` | string | Firefly III journal ID (needed for updates) |
| `account_name` | string | Merchant/store name |
| `category_mapping_id` | FK, unsigned bigint | References `category_mappings.id` |
| `current_category_id` | FK, unsigned bigint | References `categories.id` |
| `alternative_category_ids` | JSON | Snapshot of alternatives at creation time |
| `user_id` | FK, nullable | Owner from the Account |
| `budget_id` | integer, nullable | Budget from the Account |
| `transaction_amount` | decimal(12,2) | For display |
| `currency_code` | string(3) | Currency for amount display |
| `transaction_date` | datetime | For display/sorting |
| `transaction_description` | string | For display |
| `status` | string, default 'pending' | Values: pending, dismissed |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes: `status`, `user_id`, `budget_id`, `category_mapping_id`.

**Model: `PendingCategoryReview`**:
- Relationships: `belongsTo(CategoryMapping)`, `belongsTo(Category, 'current_category_id')`
- Scope `scopePending($query)`: filters `status = 'pending'`
- Scope `scopeForUser($query, $user)`: access control (see Section 5)
- Cast: `'alternative_category_ids' => 'array'`

---

## 2. AI Agent — SuggestAlternativeCategories

New PrismPHP structured agent: `app/Ai/Agents/SuggestAlternativeCategories.php`

**Model**: `gpt-5-mini`

**Input**:
- `categoryName` — current category of the mapping
- `storeName` — merchant/account name
- `exampleStores` — up to 10 other stores mapped to the same category
- `allCategories` — all available category names in the system

**Prompt**:
```
You are a financial transaction categorization assistant. A store named "{storeName}" 
is currently categorized as "{categoryName}". 
Other stores in this category include: {exampleStores}.

Suggest 1-4 alternative categories from this list that could also reasonably apply 
to this store: {allCategories}.

Only suggest categories that make genuine sense — for example, a gas station could be 
"Transportation" or "Auto & Vehicle" instead of "Fuel". Do not suggest the current 
category. If no reasonable alternatives exist, return an empty list.
```

**Output schema**:
```json
{
  "categories": ["string"]
}
```

Returns 0-4 category names from the provided list. Backend validates all returned names exist in the database.

---

## 3. CategoryMapping Edit Form — UI Changes

### 3a. Alternative Categories Multi-Select

New field below the existing `category_id` select:

```php
Select::make('alternative_category_ids')
    ->multiple()
    ->searchable()
    ->options(Category::pluck('name', 'id'))
    ->label('Alternative Categories')
    ->helperText('Optional categories this store could also belong to')
```

### 3b. "Suggest Alternative Categories" Button

An icon Action button (sparkle/wand icon) attached to the alternatives field:

**States**:
- **Idle**: Icon button, tooltip "Suggest Alternative Categories"
- **Loading**: Spinner, button disabled, helper text "AI is suggesting categories..."
- **Results**: Suggested categories shown as clickable chips below the field. Click adds to multi-select.
- **Empty**: Toast "No alternative categories suggested for this store"
- **Error**: Toast with error message

**Visibility**: Only visible when `category_id` is selected.

**Flow**:
1. User clicks sparkle icon
2. Livewire action → calls `SuggestAlternativeCategories` agent
3. Agent returns category names → mapped to IDs
4. Displayed as selectable chips
5. User clicks chips to add to multi-select
6. User saves form normally

---

## 4. parseSMSJob Integration

After `Transaction::createTransaction()` succeeds, add:

1. Check if the `CategoryMapping` for this transaction's `account_name` has non-empty `alternative_category_ids`
2. If yes, create `PendingCategoryReview`:
   - `firefly_transaction_id`, `firefly_journal_id` from Firefly III response
   - `account_name` from transaction
   - `category_mapping_id` from lookup
   - `current_category_id` from mapping
   - `alternative_category_ids` — **snapshot** from mapping (not a reference)
   - `user_id`, `budget_id` from the Account
   - `transaction_amount`, `transaction_date`, `transaction_description` for display
   - `status: pending`

No retroactive records. Only new transactions after feature deployment.

---

## 5. Review Transactions UI

### 5a. Dedicated Page: ReviewTransactions

Custom Filament Page in navigation group "Config".

**Navigation**: Sidebar item "Review Categories" with badge showing pending count.

**Table columns**:

| Column | Content |
|--------|---------|
| Date | `transaction_date`, sorted newest first |
| Store | `account_name` |
| Description | `transaction_description` |
| Amount | `transaction_amount` |
| Current Category | Badge with current category name |
| Alternatives | Inline action buttons per alternative category. Each button shows the category name. Below each button, a small "Set as default" link for that specific alternative. |
| Dismiss | X icon button |

**Access control query**:
- Admin (`user_id = 1`): no filter
- Others: `WHERE user_id = {auth.id} OR (budget_id = {auth.budget_id} AND {auth.budget_id} IS NOT NULL)`
- Only `status = 'pending'`

### 5b. Actions

**Click alternative category**:
1. Call `fireflyIII::updateTransaction()` with new `category_name`
2. Delete or dismiss the `PendingCategoryReview` record
3. Row removed from table (Livewire re-render)
4. Toast: "Transaction updated to [category]"

**Click "Set as default for [store]"**:
1. Update Firefly III transaction with new `category_name`
2. Update `CategoryMapping`:
   - Move current `category_id` into `alternative_category_ids`
   - Set `category_id` to the selected alternative
   - Remove selected alternative from `alternative_category_ids`
3. Delete/dismiss the review record
4. Toast: "Default category for [store] changed to [category]"

**Click dismiss (X)**:
1. Set `PendingCategoryReview.status = 'dismissed'`
2. Row removed from table
3. Toast: "Dismissed"

### 5c. Dashboard Widget

`TableWidget` on Dashboard:
- Shows latest 5 pending reviews (same access control)
- Columns: Date, Store, Amount, Current Category, Alternative buttons
- "View All" link to full ReviewTransactions page
- Hidden when zero pending reviews

### 5d. Navigation Badge

"Review Categories" sidebar badge with pending count. Uses `NavigationItem::badge()`. Updates on page load.

---

## 6. Error Handling

- **Firefly III API failure**: Error toast "Failed to update transaction. Please try again." Row stays.
- **Orphaned reviews** (CategoryMapping deleted): On page load, auto-dismiss reviews where `category_mapping_id` no longer exists.
- **Deleted categories in alternatives**: Skip missing category IDs when rendering buttons. Auto-dismiss review if all alternatives are gone.
- **Concurrent edits**: Check review status before updating. Second user gets "Transaction already updated" toast.
- **AI returns invalid categories**: Filter against database — only accept names matching existing categories.

---

## 7. Files to Create/Modify

### New files:
- `database/migrations/XXXX_add_alternative_category_ids_to_category_mappings.php`
- `database/migrations/XXXX_create_pending_category_reviews_table.php`
- `app/Models/PendingCategoryReview.php`
- `app/Ai/Agents/SuggestAlternativeCategories.php`
- `app/Filament/Pages/ReviewTransactions.php`
- `app/Filament/Widgets/PendingCategoryReviewsWidget.php`

### Modified files:
- `app/Models/CategoryMapping.php` — add `alternative_category_ids` field, cast, helpers
- `app/Jobs/parseSMSJob.php` — add pending review creation after transaction
- `app/Filament/Resources/CategoryMappings/CategoryMappingResource.php` — add alternatives multi-select + AI suggest button
- `app/Filament/Pages/Dashboard.php` — register new widget
