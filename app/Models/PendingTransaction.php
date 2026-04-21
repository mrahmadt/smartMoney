<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingTransaction extends Model
{
    protected $fillable = [
        'sms_id',
        'reason',
        'error_message',
        'type',
        'amount',
        'currency',
        'date',
        'description',
        'notes',
        'category_name',
        'source_account_id',
        'source_account_name',
        'destination_account_id',
        'destination_account_name',
        'tags',
        'budget_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'date' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function sms(): BelongsTo
    {
        return $this->belongsTo(SMS::class, 'sms_id');
    }
}
