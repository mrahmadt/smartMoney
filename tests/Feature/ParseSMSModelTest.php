<?php

use App\Models\ParseSMS;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- detectCategory ---

test('detectCategory returns Cash for withdrawal', function () {
    $result = ParseSMS::detectCategory(
        message: 'ATM withdrawal SAR 500',
        transactionType: 'withdrawal',
        matches: [],
    );

    expect($result['category'])->toBe('Cash');
});

test('detectCategory returns Transfer for transfer', function () {
    $result = ParseSMS::detectCategory(
        message: 'Transfer SAR 1000 to account 1234',
        transactionType: 'transfer',
        matches: [],
    );

    expect($result['category'])->toBe('Transfer');
});

test('detectCategory returns false for invalid transaction type', function () {
    $result = ParseSMS::detectCategory(
        message: 'test',
        transactionType: 'invalid',
        matches: [],
    );

    expect($result)->toBeFalse();
});

test('detectCategory returns Unknown when no match found and AI disabled', function () {
    Setting::set('parsesms_failback_detect_category_ai', 'false');

    $result = ParseSMS::detectCategory(
        message: 'Payment at unknown merchant',
        transactionType: 'payment',
        matches: ['destinationAccountName' => 'UNKNOWN_SHOP_XYZ'],
    );

    expect($result['category'])->toBe('Unknown');
});

test('detectCategory returns Unknown for deposit with no account info', function () {
    Setting::set('parsesms_failback_detect_category_ai', 'false');

    $result = ParseSMS::detectCategory(
        message: 'Deposit received',
        transactionType: 'deposit',
        matches: [],
    );

    expect($result['category'])->toBe('Unknown');
});

// --- parseSMSviaLLM ---

test('parseSMSviaLLM method exists', function () {
    expect(method_exists(ParseSMS::class, 'parseSMSviaLLM'))->toBeTrue();
});

test('parseSMSviaLLM is a static method', function () {
    $reflection = new ReflectionMethod(ParseSMS::class, 'parseSMSviaLLM');
    expect($reflection->isStatic())->toBeTrue();
});

test('ParseSMS model has no database table', function () {
    $model = new ParseSMS;
    expect($model->getTable())->toBeFalse();
});
