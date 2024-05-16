<?php

namespace App\Models\SMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValueLookup extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'parseSMS_value_lookup';
    protected $fillable = [
        'key',
        'value',
        'replaceWith',
    ];

}
