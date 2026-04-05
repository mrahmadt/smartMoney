<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
