# Alternative Categories & Transaction Review — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add alternative category support to CategoryMappings with AI suggestions, and a review UI for reassigning transactions to alternative categories in Firefly III.

**Architecture:** Filament-native (Livewire actions, tables, widgets, pages). New `pending_category_reviews` table tracks transactions needing review. New PrismPHP AI agent suggests alternatives. parseSMSJob creates review records when mappings have alternatives.

**Tech Stack:** Laravel 12, Filament v5, Livewire 4, PrismPHP (Laravel AI SDK), SQLite, Firefly III API

**Spec:** `docs/superpowers/specs/2026-04-01-alternative-categories-design.md`

---

## File Structure

### New Files
| File | Responsibility |
|------|---------------|
| `database/migrations/2026_04_01_200000_add_alternative_category_ids_to_category_mappings.php` | Add JSON column to category_mappings |
| `database/migrations/2026_04_01_200001_create_pending_category_reviews_table.php` | Create review tracking table |
| `app/Models/PendingCategoryReview.php` | Model with access control scopes |
| `app/Ai/Agents/SuggestAlternativeCategories.php` | PrismPHP agent for AI suggestions |
| `app/Filament/Pages/ReviewTransactions.php` | Dedicated review page with nav badge |
| `app/Filament/Widgets/PendingCategoryReviewsWidget.php` | Dashboard widget (top 5 reviews) |
| `resources/views/filament/pages/review-transactions.blade.php` | Blade view for review page |
| `resources/views/filament/widgets/pending-category-reviews.blade.php` | Blade view for widget |

### Modified Files
| File | Change |
|------|--------|
| `app/Models/CategoryMapping.php` | Add alternative_category_ids field, cast, helpers |
| `app/Jobs/parseSMSJob.php` | Create PendingCategoryReview after transaction |
| `app/Filament/Resources/CategoryMappings/CategoryMappingResource.php` | Add alternatives multi-select + AI suggest button |
| `app/Filament/Pages/Dashboard.php` | Register widget |
| `lang/en/menu.php` | 15 new translation keys |
| `lang/ar/menu.php` | 15 new translation keys (Arabic) |

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_04_01_200000_add_alternative_category_ids_to_category_mappings.php`
- Create: `database/migrations/2026_04_01_200001_create_pending_category_reviews_table.php`

- [ ] **Step 1: Create migration to add alternative_category_ids to category_mappings**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_mappings', function (Blueprint $table) {
            $table->json('alternative_category_ids')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_mappings', function (Blueprint $table) {
            $table->dropColumn('alternative_category_ids');
        });
    }
};
```

- [ ] **Step 2: Create migration for pending_category_reviews table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_category_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('firefly_transaction_id');
            $table->string('firefly_journal_id');
            $table->string('account_name');
            $table->foreignId('category_mapping_id')->constrained('category_mappings')->cascadeOnDelete();
            $table->foreignId('current_category_id')->constrained('categories')->cascadeOnDelete();
            $table->json('alternative_category_ids');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('budget_id')->nullable();
            $table->decimal('transaction_amount', 12, 2);
            $table->string('currency_code', 3)->nullable();
            $table->dateTime('transaction_date');
            $table->string('transaction_description');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('budget_id');
            $table->index('category_mapping_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_category_reviews');
    }
};
```

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate`
Expected: Both migrations complete successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_01_200000_add_alternative_category_ids_to_category_mappings.php \
       database/migrations/2026_04_01_200001_create_pending_category_reviews_table.php
git commit -m "feat: add alternative_category_ids column and pending_category_reviews table"
```

---

## Task 2: PendingCategoryReview Model

**Files:**
- Create: `app/Models/PendingCategoryReview.php`

- [ ] **Step 1: Create PendingCategoryReview model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PendingCategoryReview extends Model
{
    protected $fillable = [
        'firefly_transaction_id', 'firefly_journal_id', 'account_name',
        'category_mapping_id', 'current_category_id', 'alternative_category_ids',
        'user_id', 'budget_id', 'transaction_amount', 'currency_code',
        'transaction_date', 'transaction_description', 'status',
    ];

    protected $casts = [
        'alternative_category_ids' => 'array',
        'transaction_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function categoryMapping(): BelongsTo
    {
        return $this->belongsTo(CategoryMapping::class);
    }

    public function currentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'current_category_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser(Builder $query, $user): Builder
    {
        if ($user->id === 1) {
            return $query;
        }
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->budget_id) {
                $q->orWhere('budget_id', $user->budget_id);
            }
        });
    }

    public function getAlternativeCategories(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->alternative_category_ids ?? [];
        if (empty($ids)) {
            return Category::query()->whereRaw('1=0')->get();
        }
        return Category::whereIn('id', $ids)->get();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/PendingCategoryReview.php
git commit -m "feat: add PendingCategoryReview model with access control scopes"
```

---

## Task 3: Update CategoryMapping Model

**Files:**
- Modify: `app/Models/CategoryMapping.php`

- [ ] **Step 1: Add alternative_category_ids to fillable, add cast, add helpers**

Add to `$fillable`: `'alternative_category_ids'`

Add `$casts`:
```php
protected $casts = [
    'alternative_category_ids' => 'array',
];
```

Add methods:
```php
public function alternativeCategories(): \Illuminate\Database\Eloquent\Collection
{
    $ids = $this->alternative_category_ids ?? [];
    if (empty($ids)) {
        return Category::query()->whereRaw('1=0')->get();
    }
    return Category::whereIn('id', $ids)->get();
}

public function hasAlternatives(): bool
{
    return !empty($this->alternative_category_ids);
}
```

- [ ] **Step 2: Add lookupMapping static method**

```php
public static function lookupMapping(string $accountName): ?self
{
    return static::with('category')
        ->whereRaw('LOWER(account_name) = ?', [strtolower(trim($accountName))])
        ->first();
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/CategoryMapping.php
git commit -m "feat: add alternative categories support to CategoryMapping model"
```

---

## Task 4: AI Agent — SuggestAlternativeCategories

**Files:**
- Create: `app/Ai/Agents/SuggestAlternativeCategories.php`

- [ ] **Step 1: Create the agent following existing SMSCategory pattern**

```php
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

Only suggest categories that make genuine sense. Do NOT suggest the current category "{$this->categoryName}". Only pick from the provided list. If no reasonable alternatives exist, return an empty list. Maximum 4 suggestions.
PROMPT;
    }

    public function messages(): iterable { return []; }
    public function tools(): iterable { return []; }

    public function schema(JsonSchema $schema): array
    {
        return [
            'categories' => $schema->array(
                $schema->string()->description('A category name from the provided list')
            )->required()->description('0-4 alternative category names. Empty array if no reasonable alternatives.'),
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Ai/Agents/SuggestAlternativeCategories.php
git commit -m "feat: add SuggestAlternativeCategories AI agent"
```

---

## Task 5: Update CategoryMapping Form with Alternatives + AI Suggest

**Files:**
- Modify: `app/Filament/Resources/CategoryMappings/CategoryMappingResource.php`

- [ ] **Step 1: Add imports**

```php
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Ai\Agents\SuggestAlternativeCategories;
use App\Models\Setting;
```

- [ ] **Step 2: Add alternatives multi-select and AI suggest button to form**

Add after the `category_id` Select:
```php
Select::make('alternative_category_ids')
    ->label(__('menu.alternative_categories'))
    ->multiple()
    ->searchable()
    ->options(Category::orderBy('name')->pluck('name', 'id'))
    ->helperText(__('menu.alternative_categories_hint')),
Actions::make([
    Action::make('suggestAlternatives')
        ->label(__('menu.suggest_alternatives'))
        ->icon('heroicon-o-sparkles')
        ->color('gray')
        ->visible(fn ($get) => $get('category_id') !== null)
        ->action(function ($get, $set, $livewire) {
            // ... AI agent call, set alternatives
        }),
]),
```

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Resources/CategoryMappings/CategoryMappingResource.php
git commit -m "feat: add alternative categories multi-select and AI suggest button"
```

---

## Task 6: Update parseSMSJob to Create Pending Reviews

**Files:**
- Modify: `app/Jobs/parseSMSJob.php`

- [ ] **Step 1: Add imports**

```php
use App\Models\CategoryMapping;
use App\Models\PendingCategoryReview;
```

- [ ] **Step 2: Add review creation after successful transaction**

Inside the `if ($status['success'])` block, after Alert::newTransaction(), add:

```php
$merchantName = $transaction['OtherAccountName'] ?? $transaction['OtherAccountNumber'] ?? null;
if ($merchantName) {
    $mapping = CategoryMapping::lookupMapping($merchantName);
    if ($mapping && $mapping->hasAlternatives()) {
        PendingCategoryReview::create([
            'firefly_transaction_id' => $status['transaction_id'],
            'firefly_journal_id' => $status['attributes']->transaction_journal_id ?? $status['transaction_id'],
            'account_name' => $merchantName,
            'category_mapping_id' => $mapping->id,
            'current_category_id' => $mapping->category_id,
            'alternative_category_ids' => $mapping->alternative_category_ids,
            'user_id' => $localAccount?->user_id,
            'budget_id' => $localAccount?->budget_id ?? $budget_id,
            'transaction_amount' => $status['attributes']->amount ?? $transaction['amount'] ?? 0,
            'currency_code' => $status['attributes']->currency_code ?? $transaction['currency'] ?? null,
            'transaction_date' => $status['attributes']->date ?? now(),
            'transaction_description' => $status['attributes']->description ?? $transaction['description'] ?? '',
            'status' => 'pending',
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/parseSMSJob.php
git commit -m "feat: create pending category reviews in parseSMSJob"
```

---

## Task 7: Review Transactions Page

**Files:**
- Create: `app/Filament/Pages/ReviewTransactions.php`
- Create: `resources/views/filament/pages/review-transactions.blade.php`

- [ ] **Step 1: Create the ReviewTransactions Filament page**

Page implements `HasTable`. Key features:
- Navigation badge with pending count
- Access control via `scopeForUser`
- Table with date, store, description, amount, current category badge, alternative category buttons
- `applyAlternative()` — updates Firefly III + dismisses review
- `setDefault()` — updates Firefly III + swaps CategoryMapping default/alternatives
- `dismissReview()` — sets status to dismissed
- Orphan cleanup on mount

- [ ] **Step 2: Create Blade view**

```blade
<x-filament-panels::page>
    {{ $this->table }}
</x-filament-panels::page>
```

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Pages/ReviewTransactions.php resources/views/filament/pages/review-transactions.blade.php
git commit -m "feat: add ReviewTransactions page with inline category actions"
```

---

## Task 8: Dashboard Widget

**Files:**
- Create: `app/Filament/Widgets/PendingCategoryReviewsWidget.php`
- Create: `resources/views/filament/widgets/pending-category-reviews.blade.php`
- Modify: `app/Filament/Pages/Dashboard.php`

- [ ] **Step 1: Create PendingCategoryReviewsWidget**

Shows top 5 pending reviews with alternative category buttons and dismiss. Conditionally shown via `canView()`.

- [ ] **Step 2: Create Blade widget view**

Styled banner (matching existing PinnedAlertsBanner pattern) with inline alternative category chips and "View All" link.

- [ ] **Step 3: Register widget in Dashboard.php**

```php
if (\App\Filament\Widgets\PendingCategoryReviewsWidget::canView()) {
    $widgets[] = \App\Filament\Widgets\PendingCategoryReviewsWidget::class;
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Widgets/PendingCategoryReviewsWidget.php \
       resources/views/filament/widgets/pending-category-reviews.blade.php \
       app/Filament/Pages/Dashboard.php
git commit -m "feat: add pending category reviews dashboard widget"
```

---

## Task 9: Translations

**Files:**
- Modify: `lang/en/menu.php`
- Modify: `lang/ar/menu.php`

- [ ] **Step 1: Add 15 new translation keys to both en and ar**

Keys: `alternative_categories`, `alternative_categories_hint`, `suggest_alternatives`, `select_category_first`, `no_categories_available`, `no_alternatives_suggested`, `alternatives_suggested`, `ai_error`, `review_categories`, `pending_category_reviews`, `set_as_default`, `dismiss`, `dismissed`, `view_all`, `transaction_updated`, `default_changed`, `update_failed`, `review_already_processed`

- [ ] **Step 2: Commit**

```bash
git add lang/en/menu.php lang/ar/menu.php
git commit -m "feat: add alternative categories translation keys (en + ar)"
```

---

## Task 10: Verification

- [ ] **Step 1: Run migrations** — `php artisan migrate` (exit 0)
- [ ] **Step 2: Run tests** — `php artisan test` (155 pass, 16 pre-existing failures)
- [ ] **Step 3: Check app boots** — `php artisan about` (no errors)
- [ ] **Step 4: Verify spec coverage** — line-by-line against design spec
