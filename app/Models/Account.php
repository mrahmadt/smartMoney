<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Services\fireflyIII;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class Account extends Model
{
    protected $fillable = [
        'user_id',
        'budget_id',
        'sender_id',
        'shortcodes',
        'firefly_account_name',
        'iban',
        'account_number',
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
                    if ($local->iban !== ($remoteAccount->attributes->iban ?? null)) {
                        $changes['iban'] = $remoteAccount->attributes->iban ?? null;
                    }
                    if ($local->account_number !== ($remoteAccount->attributes->account_number ?? null)) {
                        $changes['account_number'] = $remoteAccount->attributes->account_number ?? null;
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
                        'iban' => $remoteAccount->attributes->iban ?? null,
                        'account_number' => $remoteAccount->attributes->account_number ?? null,
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
     * Priority 3: guess by matching shortcode against IBAN/account_number suffix (if enabled)
     * Priority 4: sender match only (no shortcode match) — last resort (if enabled)
     *
     * @return array{account: static, match: string}|null
     */
    public static function findBySenderAndShortcode(string $senderName, string $shortcode): ?array
    {
        $senderName = strtolower($senderName);

        // Get sender record
        $sender = SMSSender::whereRaw('LOWER(sender) = ?', [$senderName])->first();

        $all = static::all();

        // Priority 1: sender + shortcode match
        if ($sender) {
            foreach ($all as $account) {
                if ($account->sender_id === $sender->id && $account->hasShortcode($shortcode)) {
                    return ['account' => $account, 'match' => 'shortcode'];
                }
            }
        }

        // Priority 2: no sender set + shortcode match
        foreach ($all as $account) {
            if ($account->sender_id === null && $account->hasShortcode($shortcode)) {
                return ['account' => $account, 'match' => 'shortcode'];
            }
        }

        // Priority 3: guess by matching shortcode against IBAN or account_number suffix
        if ($sender && Setting::getBool('account_guess_by_shortcode', false)) {
            Log::debug('Attempting to guess account by matching shortcode against IBAN/account_number suffix', ['sender' => $senderName, 'shortcode' => $shortcode]);
            $cleanShortcode = preg_replace('/[^0-9]/', '', $shortcode);
            Log::debug('Cleaned shortcode for matching', ['cleanShortcode' => $cleanShortcode]);

            if (strlen($cleanShortcode) >= 3) {
                foreach ([true,false] as $method){
                    foreach ($all as $account) {
                        if($method == true){
                            if ($account->sender_id !== $sender->id) {
                                continue;
                            }
                        }
                        $cleanIban = $account->iban ? preg_replace('/[\s\-]/', '', $account->iban) : null;
                        $cleanAccountNumber = $account->account_number ? preg_replace('/[\s\-]/', '', $account->account_number) : null;
                        Log::debug('Cleaned IBAN and account number for matching', ['cleanIban' => $cleanIban, 'cleanAccountNumber' => $cleanAccountNumber]);
                        if ($cleanIban && str_ends_with($cleanIban, $cleanShortcode)) {
                            return ['account' => $account, 'match' => 'guess'];
                        }
                        if ($cleanAccountNumber && str_ends_with($cleanAccountNumber, $cleanShortcode)) {
                            return ['account' => $account, 'match' => 'guess'];
                        }
                    }
                }

            }

        }

        // Priority 4: sender match only (no shortcode match) — last resort
        if ($sender && Setting::getBool('account_fallback_sender_only', false)) {
            foreach ($all as $account) {
                if ($account->sender_id === $sender->id) {
                    return ['account' => $account, 'match' => 'sender_fallback'];
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
