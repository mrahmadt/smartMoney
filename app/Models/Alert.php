<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\WebPush;
use App\Notifications\AlertEmail;
use App\Jobs\SendBatchedNotifications;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\fireflyIII;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_journal_id',
        'title',
        'message',
        'data',
        'is_read',
        'is_pinned',
        'notified_at',
        'topic',
    ];
    protected $casts = [
        'is_read' => 'boolean',
        'is_pinned' => 'boolean',
        'data' => 'array',
        'notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function newTransaction($transaction, $user)
    {
        app()->setLocale($user->language ?? 'en');

        $amount = number_format($transaction->amount, 2);
        $amount = str_replace('.00', '', $amount);
        $currency = $transaction->currency_symbol ?? '';
        if ($transaction->type == 'withdrawal') {
            $type = __('alert.spent');
        } elseif ($transaction->type == 'deposit') {
            $type = __('alert.received');
        } elseif ($transaction->type == 'transfer') {
            $type = __('alert.transferred');
        } else {
            $type = __('alert.processed');
        }
        $destination_name = $transaction->destination_name ?? '';
        $source_name = $transaction->source_name ?? '';
        $category_name = !empty($transaction->category_name) ? ' (' . $transaction->category_name . ') ' : '';

        $title = $type . ' ' . $amount . ' ' . $currency;
        $message = $title . "\n" . $destination_name . $category_name . "\n" . $source_name;

        Alert::createAlert(
            title: $title,
            message: $message,
            user: $user,
            transaction_journal_id: $transaction->transaction_journal_id ?? null,
            data: [
                'transaction_id' => $transaction->transaction_journal_id
            ],
            topic: 'transaction'
        );
    }
    public static function abnormalTransaction($user_id, $transaction_journal_id, $amount, $average_amount, $difference_percentage){
                $user = is_numeric($user_id) ? User::find($user_id) : $user_id;
                if (!$user) return;
                app()->setLocale($user->language ?? 'en');

                Alert::createAlert(
                    title: __('alert.abnormal_title'),
                    message: __('alert.abnormal_message', [
                        'amount' => number_format($amount, 2, '.', ','),
                        'average_amount' => number_format($average_amount, 2, '.', ','),
                        'percentage' => number_format($difference_percentage, 2, '.', ','),
                    ]),
                    user: $user,
                    transaction_journal_id: $transaction_journal_id,
                    pin: true,
                    data: [
                        'amount' => $amount,
                        'average_amount' => $average_amount,
                        'difference_percentage' => $difference_percentage
                    ],
                    topic: 'abnormal'
                );
    }

    public static function createAlert($title, $message, $user, $transaction_journal_id = null, $data = [], $pin = false, $topic = null)
    {
        $alert = new Alert();
        $alert->title = $title;
        $alert->transaction_journal_id = $transaction_journal_id;
        $alert->user_id = $user->id;
        $alert->message = $message;
        if ($data) $alert->data = $data;
        if ($pin) $alert->is_pinned = true;
        if ($topic) $alert->topic = $topic;
        $alert->save();

        $delay = (int) Setting::get('alert_batch_delay', 5);
        if (config('queue.default') === 'sync') {
            SendBatchedNotifications::dispatchSync($user->id);
        } else {
            SendBatchedNotifications::dispatch($user->id)->delay(now()->addSeconds($delay));
        }
    }

    /**
     * Create alert for user + copy to admin (user 1) if different.
     */
    public static function createAlertWithAdminCopy($title, $message, $user_id, $transaction_journal_id = null, $data = [], $pin = false, $topic = null)
    {
        $user = is_numeric($user_id) ? User::find($user_id) : $user_id;
        $userId = $user ? $user->id : 1;

        if (!$user) {
            $user = User::find(1);
        }
        if (!$user) return;

        app()->setLocale($user->language ?? 'en');
        self::createAlert($title, $message, $user, $transaction_journal_id, $data, $pin, $topic);

        // Copy to admin if different user
        if ($userId !== 1) {
            $admin = User::find(1);
            if ($admin) {
                app()->setLocale($admin->language ?? 'en');
                self::createAlert($title, $message, $admin, $transaction_journal_id, $data, $pin, $topic);
            }
        }
    }
}
