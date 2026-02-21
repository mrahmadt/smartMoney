<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSSender extends Model
{
    use HasFactory;
    protected $table = 'smssenders';
    protected $fillable = [
        'sender',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
    public static function isValidSender($sender)
    {
        $dbSenders = self::where('is_active', true)->pluck('sender')->toArray();
        $dbSenders = array_map('strtolower', $dbSenders);
        return in_array(strtolower($sender), $dbSenders);
    }
    public function regularExps()
    {
        return $this->hasMany(SMSRegularExp::class, 'sender_id');
    }
    
    public function activeRegularExps()
    {
        return $this->hasMany(SMSRegularExp::class, 'sender_id')
            ->where('is_active', true);
    }

}
