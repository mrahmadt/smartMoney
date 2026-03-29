<?php

use App\Models\SMS;
use App\Models\User;
use App\Models\Alert;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::set('parsesms_store_invalid_sms', 'true');
    Setting::set('parsesms_min_sms_length', '10');

    Notification::fake();
    User::factory()->create(['id' => 1]);
});

// --- generateHash ---

test('generateHash returns 32-char md5 string', function () {
    $hash = SMS::generateHash('TestBank', 'Hello');
    expect($hash)->toHaveLength(32);
});

test('generateHash is consistent for same input', function () {
    expect(SMS::generateHash('Bank', 'msg'))->toBe(SMS::generateHash('Bank', 'msg'));
});

test('generateHash is case-insensitive on sender', function () {
    expect(SMS::generateHash('BANK', 'msg'))->toBe(SMS::generateHash('bank', 'msg'));
});

test('generateHash differs for different messages', function () {
    expect(SMS::generateHash('Bank', 'A'))->not->toBe(SMS::generateHash('Bank', 'B'));
});

test('generateHash differs for different senders', function () {
    expect(SMS::generateHash('BankA', 'msg'))->not->toBe(SMS::generateHash('BankB', 'msg'));
});

// --- isDuplicate ---

test('isDuplicate returns false when no matching SMS exists', function () {
    expect(SMS::isDuplicate('Bank', 'new message'))->toBeFalse();
});

test('isDuplicate returns true when matching SMS exists', function () {
    SMS::create([
        'sender' => 'bank',
        'message' => 'existing message',
        'message_hash' => SMS::generateHash('Bank', 'existing message'),
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    expect(SMS::isDuplicate('Bank', 'existing message'))->toBeTrue();
});

// --- removeHiddenChars ---

test('removeHiddenChars strips invisible unicode characters', function () {
    $clean = SMS::removeHiddenChars("Hello\x00World");
    expect($clean)->toBe('HelloWorld');
});

test('removeHiddenChars preserves normal text', function () {
    $text = "Purchase\nAmount: SAR 100.00";
    expect(SMS::removeHiddenChars($text))->toBe($text);
});

test('removeHiddenChars preserves Arabic text', function () {
    $text = "عملية شراء مبلغ 100 ريال";
    expect(SMS::removeHiddenChars($text))->toBe($text);
});

// --- isValidBankTransaction ---

test('isValidBankTransaction rejects message with no numbers', function () {
    expect(SMS::isValidBankTransaction('No numbers here at all in this message text', false))->toBeFalse();
});

test('isValidBankTransaction rejects tiny messages', function () {
    Setting::set('parsesms_min_sms_length', '30');
    expect(SMS::isValidBankTransaction('Short 123', false))->toBeFalse();
});

test('isValidBankTransaction accepts valid transaction message', function () {
    expect(SMS::isValidBankTransaction('Purchase Amount SAR 100 at STARBUCKS Account 1234', false))->toBeTrue();
});

// --- processInvalidSMS ---

test('processInvalidSMS saves error and marks as invalid when store_invalid_sms is true', function () {
    Setting::set('parsesms_store_invalid_sms', 'true');

    $sms = SMS::create([
        'sender' => 'bank',
        'message' => 'test',
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    SMS::processInvalidSMS($sms, 'Test error');

    $sms->refresh();
    expect($sms->is_valid)->toBeFalse();
    expect($sms->is_processed)->toBeTrue();
    expect($sms->errors)->toBe(['reason' => 'Test error']);
});

test('processInvalidSMS saves when keep is true even if store_invalid_sms is false', function () {
    Setting::set('parsesms_store_invalid_sms', 'false');

    $sms = SMS::create([
        'sender' => 'bank',
        'message' => 'test',
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    SMS::processInvalidSMS($sms, 'Kept error', keep: true);

    $sms->refresh();
    expect($sms->is_valid)->toBeFalse();
    expect($sms->is_processed)->toBeTrue();
});

test('processInvalidSMS deletes SMS when store_invalid_sms is false and keep is false', function () {
    Setting::set('parsesms_store_invalid_sms', 'false');

    $sms = SMS::create([
        'sender' => 'bank',
        'message' => 'test',
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    $smsId = $sms->id;
    SMS::processInvalidSMS($sms, 'Delete me', keep: false);

    expect(SMS::find($smsId))->toBeNull();
});

test('processInvalidSMS accepts array errors', function () {
    Setting::set('parsesms_store_invalid_sms', 'true');

    $sms = SMS::create([
        'sender' => 'bank',
        'message' => 'test',
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    SMS::processInvalidSMS($sms, ['code' => 'ERR01', 'detail' => 'bad data']);

    $sms->refresh();
    expect($sms->errors)->toBe(['code' => 'ERR01', 'detail' => 'bad data']);
});
