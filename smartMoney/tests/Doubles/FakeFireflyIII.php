<?php

/**
 * Test double for App\Services\fireflyIII.
 * Loaded BEFORE autoloader to intercept `new fireflyIII()` calls in production code.
 * Must match the method signatures that are called with named parameters.
 */

namespace App\Services;

class fireflyIII
{
    private static $responses = [];
    private static $callLog = [];
    private static $callIndex = 0;

    public static function resetMock(): void
    {
        static::$responses = [];
        static::$callLog = [];
        static::$callIndex = 0;
    }

    public static function setResponses(array $responses): void
    {
        static::$responses = $responses;
        static::$callIndex = 0;
    }

    public static function getCallLog(): array
    {
        return static::$callLog;
    }

    public function getTransactions($start = null, $end = null, $filter = [], $limit = 1000, $page = 1, $type = null, &$meta = [])
    {
        static::$callLog[] = [
            'start' => $start,
            'end' => $end,
            'filter' => $filter,
            'limit' => $limit,
            'page' => $page,
            'type' => $type,
        ];

        $index = static::$callIndex++;
        return static::$responses[$index] ?? false;
    }
}
