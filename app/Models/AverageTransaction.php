<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AverageTransaction extends Model
{
    use HasFactory;
    protected $table = 'average_transactions';
    protected $fillable = [
        'type',
        'attribute',
        'key',
        'total',
        'total_amount',
        'average_amount',
    ];

}
