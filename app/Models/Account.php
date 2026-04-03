<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\fireflyIII;

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
                if ($account->sender_id === $sender->id && is_array($account->shortcodes) && in_array($shortcode, $account->shortcodes)) {
                    return $account;
                }
            }
        }

        // Priority 2: no sender set + shortcode match
        foreach ($all as $account) {
            if ($account->sender_id === null && is_array($account->shortcodes) && in_array($shortcode, $account->shortcodes)) {
                return $account;
            }
        }

        return null;
    }
}
