<?php
// app/Models/Keyword.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $table = 'keywords';

    protected $fillable = [
        'keyword',
        'is_regularExp',
        'replaceWith',
        'keyword_type',
        'is_active',
        'channel', // Optional: to specify if this keyword is for SMS, email, etc.
    ];

    protected function casts(): array
    {
        return [
            'is_regularExp' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
    public static function returnActiveKeywordsByType($type, $is_regularExp = true, $channel = 'sms'): array
    {
        return self::where('keyword_type', $type)
            ->where('is_regularExp', $is_regularExp)
            ->where('is_active', true)
            ->where('channel', $channel)
            ->pluck('keyword')
            ->toArray();
    }


    public static function regex_replaceWith(): array
    {
        return self::returnActiveKeywordsByType('replace', true, 'sms');
    }

    public static function str_replaceWith(): array
    {
        return self::returnActiveKeywordsByType('replace', false, 'sms');
    }
    
    public static function regex_phoneNumbers(): array
    {
        return self::returnActiveKeywordsByType('phone', true, 'sms');
    }

    public static function regex_passcodes(): array
    {
        return self::returnActiveKeywordsByType('passcodes', true, 'sms');
    }
    public static function regex_misc(): array
    {
        return self::returnActiveKeywordsByType('misc', true, 'sms');
    }
    public static function regex_date(): array
    {
        return self::returnActiveKeywordsByType('date', true, 'sms');
    }
    public static function regex_breaks(): array
    {
        return self::returnActiveKeywordsByType('breaks', false, 'sms');
    }
    public static function regex_urls(): array
    {
        return self::returnActiveKeywordsByType('url', true, 'sms');
    }

    public static function regex_ignoreSMS(): array
    {
        return self::returnActiveKeywordsByType('ignore', true, 'sms');
    }
    
    public static function str_ignoreSMS(): array
    {
        return self::returnActiveKeywordsByType('ignore', false, 'sms');
    }


}