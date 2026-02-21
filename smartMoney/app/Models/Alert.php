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

    public static function newTransaction($transaction)
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
        
        $source_id = $transaction['source_id'];
        $fireflyIII = new fireflyIII();
        $account = $fireflyIII->getAccount($source_id);
        $accountCode = $fireflyIII->getAccountConfig($account->data->attributes);

        $title = $type . ' ' . $amount . ' ' . $currency;
        $message = $destination_name . $category_name . "\n" . $source_name;

        $user_id = 1;
        if($accountCode['user_id']){
            $user_id = $accountCode['user_id'];
        }
        $user = User::find($user_id);

        Alert::notify(
            title: $title,
            message: $message,
            user: $user,
            data: [
                'transaction_id' => $transaction['transaction_journal_id']
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
