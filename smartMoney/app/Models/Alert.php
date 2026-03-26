<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Notifications\WebPush;
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
    ];
    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function newTransaction($transaction, $user)
    {
        $amount = number_format($transaction['amount'], 2);
        $amount = str_replace('.00', '', $amount);
        $currency = $transaction['currency'] ?? '';
        // $description = $transaction['description'] ?? '';

        if ($transaction['type'] = 'withdrawal') {
            $type = 'Spent';
        } elseif ($transaction['type'] = 'deposit') {
            $type = 'Received';
        } elseif ($transaction['type'] = 'transfer') {
            $type = 'Transferred';
        } else {
            $type = 'Processed';
        }
        $destination_name = $transaction['destination_name'] ?? '';
        $source_name = $transaction['source_name'] ?? '';
        $category_name = ' (' . $transaction['category_name'] . ') ' ?? '';

        $title = $type . ' ' . $amount . ' ' . $currency;
        $message = $destination_name . $category_name . "\n" . $source_name;
        Alert::notify(
            title: $title,
            message: $message,
            user: $user,
            data: [
                'transaction_id' => $transaction['transaction_journal_id']
            ]
        );
    }
    public static function abnormalTransaction($user_id, $transaction_journal_id, $amount, $average_amount, $difference_percentage){
                Alert::createAlert(
                    title: 'Abnormal Transaction',
                    message: "An abnormal transaction has been detected. Amount: ".number_format($amount, 2, '.',',').". Average amount: ".number_format($average_amount, 2, '.',',').", (".number_format($difference_percentage, 2, '.',',')."%).",
                    user: $user_id, // You can change this to notify a specific user
                    transaction_journal_id: $transaction_journal_id,
                    data: [
                        'amount' => $amount,
                        'average_amount' => $average_amount,
                        'difference_percentage' => $difference_percentage
                    ]
                );
    }

    public static function notify($title, $message, $user, $transaction_journal_id = null, $data = [])
    {
        $user->notify(new WebPush($title, $message));
    }

    public static function createAlert($title, $message, $user, $transaction_journal_id = null, $data = [])
    {
        $user->notify(new WebPush($title, $message));
        $alert = new Alert();
        $alert->title = $title;
        $alert->transaction_journal_id = $transaction_journal_id;
        $alert->user_id = $user->id;
        $alert->message = $message;
        if ($data) $alert->data = $data;
        $alert->save();
    }
}
