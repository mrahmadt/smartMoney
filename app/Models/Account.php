<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Services\fireflyIII;
use App\Models\Setting;

class Account extends Model
{
    protected $fillable = [
        'user_id',
        'budget_id',
        'sender_id',
        'shortcodes',
        'firefly_account_name',
        'firefly_account_id',
        'currency_code',
    ];

    protected $casts = [
        'shortcodes' => 'array',
        'firefly_account_id' => 'integer',
        'budget_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SMSSender::class, 'sender_id');
    }

    /**
     * Get transaction filter array for the current user.
     * Admin (user_id=1) gets no filter (sees all).
     * Other users: filter by source_id matching owned accounts or shared budget accounts.
     *
     * @return array<string, mixed>
     */
    public static function getTransactionFilter(): array
    {
        $user = Auth::user();
        if ($user->id === 1) {
            return [];
        }

        $accountIds = static::where('user_id', $user->id)
            ->when($user->budget_id, fn ($q) => $q->orWhere('budget_id', $user->budget_id))
            ->pluck('firefly_account_id')
            ->toArray();

        return !empty($accountIds) ? ['source_id' => $accountIds] : [];
    }

    /**
     * One-way sync from Firefly III to local database.
     * Only syncs asset accounts.
     */
    public static function syncFromFirefly(): array
    {
        $firefly = new fireflyIII();
        $remoteIds = [];
        $created = 0;
        $updated = 0;
        $deleted = 0;

        $totalPages = 1;
        for ($page = 1; $page <= $totalPages; $page++) {
            $response = $firefly->getAccounts('asset');
            if (!$response || !isset($response->data)) break;

            if ($page === 1 && isset($response->meta->pagination->total_pages)) {
                $totalPages = $response->meta->pagination->total_pages;
            }

            foreach ($response->data as $remoteAccount) {
                $fireflyId = (int) $remoteAccount->id;
                $remoteIds[] = $fireflyId;

                $local = static::where('firefly_account_id', $fireflyId)->first();

                if ($local) {
                    $changes = [];
                    if ($local->firefly_account_name !== $remoteAccount->attributes->name) {
                        $changes['firefly_account_name'] = $remoteAccount->attributes->name;
                    }
                    if ($local->currency_code !== ($remoteAccount->attributes->currency_code ?? null)) {
                        $changes['currency_code'] = $remoteAccount->attributes->currency_code ?? null;
                    }
                    if (!empty($changes)) {
                        $local->update($changes);
                        $updated++;
                    }
                } else {
                    static::create([
                        'firefly_account_name' => $remoteAccount->attributes->name,
                        'firefly_account_id' => $fireflyId,
                        'currency_code' => $remoteAccount->attributes->currency_code ?? null,
                    ]);
                    $created++;
                }
            }
        }

        // Delete local records not found in Firefly III
        $deletedCount = static::whereNotIn('firefly_account_id', $remoteIds)->delete();
        $deleted = $deletedCount;

        return compact('created', 'updated', 'deleted');
    }

    /**
     * Find account by SMS sender name and shortcode.
     * Priority 1: sender + shortcode match
     * Priority 2: no sender set + shortcode match
     * Priority 3: sender match only (no shortcode match) — last resort
     * Returns null if no match.
     */
    public static function findBySenderAndShortcode(string $senderName, string $shortcode): ?static
    {
        $senderName = strtolower($senderName);

        // Get sender record
        $sender = SMSSender::whereRaw('LOWER(sender) = ?', [$senderName])->first();

        $all = static::all();

        // Priority 1: sender + shortcode match
        if ($sender) {
            foreach ($all as $account) {
                if ($account->sender_id === $sender->id && $account->hasShortcode($shortcode)) {
                    return $account;
                }
            }
        }

        // Priority 2: no sender set + shortcode match
        foreach ($all as $account) {
            if ($account->sender_id === null && $account->hasShortcode($shortcode)) {
                return $account;
            }
        }

        // Priority 3: sender match only (no shortcode match) — last resort
        if ($sender && Setting::getBool('account_fallback_sender_only', false)) {
            foreach ($all as $account) {
                if ($account->sender_id === $sender->id) {
                    return $account;
                }
            }
        }

        return null;
    }

    /**
     * Check if this account has the given shortcode.
     * Supports both flat ["xxx"] and structured [{"shortcode":"xxx","budget_id":5}] formats.
     */
    public function hasShortcode(string $shortcode): bool
    {
        if (!is_array($this->shortcodes)) {
            return false;
        }

        foreach ($this->shortcodes as $entry) {
            if (is_string($entry) && $entry === $shortcode) {
                return true;
            }
            if (is_array($entry) && ($entry['shortcode'] ?? null) === $shortcode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the budget_id for a specific shortcode.
     * Returns shortcode-specific budget if defined, otherwise the account's default budget_id.
     */
    public function getBudgetForShortcode(string $shortcode): ?int
    {
        if (is_array($this->shortcodes)) {
            foreach ($this->shortcodes as $entry) {
                if (is_array($entry) && ($entry['shortcode'] ?? null) === $shortcode) {
                    if (!empty($entry['budget_id'])) {
                        return (int) $entry['budget_id'];
                    }
                    break;
                }
            }
        }

        return $this->budget_id;
    }

    /**
     * Get flat list of shortcode strings (for display).
     */
    public function getShortcodeList(): array
    {
        if (!is_array($this->shortcodes)) {
            return [];
        }
        $data = array_map(function ($entry) {
            return is_string($entry) ? $entry : ($entry['shortcode'] ?? '');
        }, $this->shortcodes);
        return $data;
    }
}
