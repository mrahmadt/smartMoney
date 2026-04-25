<?php

// app/Models/Keyword.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keyword extends Model
{
    use HasFactory;

    protected $table = 'keywords';

    protected $fillable = [
        'name',
        'keyword',
        'is_regularExp',
        'replaceWith',
        'keyword_type',
        'is_active',
        'sender_id',
    ];

    protected function casts(): array
    {
        return [
            'is_regularExp' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SMSSender::class, 'sender_id');
    }

    public static function returnActiveKeywordsByType($type, $is_regularExp = true, ?int $senderId = null): array
    {
        $query = self::where('keyword_type', $type)
            ->where('is_regularExp', $is_regularExp)
            ->where('is_active', true);

        if ($senderId !== null) {
            $query->where(function ($q) use ($senderId) {
                $q->where('sender_id', $senderId)
                    ->orWhereNull('sender_id');
            });
        } else {
            $query->whereNull('sender_id');
        }

        return $query->pluck('keyword')->toArray();
    }

    public static function regex_replaceWith(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('replace', true, $senderId);
    }

    public static function str_replaceWith(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('replace', false, $senderId);
    }

    public static function regex_phoneNumbers(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('phone', true, $senderId);
    }

    public static function regex_passcodes(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('passcodes', true, $senderId);
    }

    public static function regex_misc(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('misc', true, $senderId);
    }

    public static function regex_date(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('date', true, $senderId);
    }

    public static function regex_breaks(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('breaks', true, $senderId);
    }

    public static function non_regex_breaks(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('breaks', false, $senderId);
    }

    public static function regex_urls(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('url', true, $senderId);
    }

    public static function regex_ignoreSMS(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('ignore', true, $senderId);
    }

    public static function str_ignoreSMS(?int $senderId = null): array
    {
        return self::returnActiveKeywordsByType('ignore', false, $senderId);
    }
}
