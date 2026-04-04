<?php

use App\Models\SMSRegularExp;
use App\Models\SMSSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestSender($name = 'testbank'): int
{
    return DB::table('smssenders')->insertGetId([
        'sender' => $name,
        'is_active' => true,
    ]);
}

function createRegExp(int $senderId, array $overrides = []): SMSRegularExp
{
    return SMSRegularExp::create(array_merge([
        'sender_id' => $senderId,
        'transactionType' => 'withdrawal',
        'regularExp' => '/Purchase.*Amount:\s*(?P<currency>\w+)\s+(?P<amount>[\d,.]+).*At:\s*(?P<OtherAccountName>.+?)\\n.*Account:\s*(?P<MyAccountNumber>\d+)/s',
        'regularExpMD5' => md5('/test/'),
        'createdBy' => 'system',
        'data' => [],
        'is_active' => true,
        'is_validTransaction' => true,
        'is_validRegularExp' => true,
    ], $overrides));
}

// --- findValidRegExp ---

test('findValidRegExp returns match with named groups', function () {
    $senderId = createTestSender();
    createRegExp($senderId);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    $result = SMSRegularExp::findValidRegExp($senderId, $message);

    expect($result)->not->toBeFalse();
    expect($result['transactionType'])->toBe('withdrawal');
    expect($result['matches']['amount'])->toBe('100.00');
    expect($result['matches']['currency'])->toBe('SAR');
    expect($result['matches']['OtherAccountName'])->toBe('STARBUCKS');
    expect($result['matches']['MyAccountNumber'])->toBe('1234');
});

test('findValidRegExp returns false when no regex matches', function () {
    $senderId = createTestSender();
    createRegExp($senderId);

    $result = SMSRegularExp::findValidRegExp($senderId, 'Completely unrelated text');

    expect($result)->toBeFalse();
});

test('findValidRegExp ignores inactive regex', function () {
    $senderId = createTestSender();
    createRegExp($senderId, ['is_active' => false]);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    expect(SMSRegularExp::findValidRegExp($senderId, $message))->toBeFalse();
});

test('findValidRegExp ignores invalid transaction regex', function () {
    $senderId = createTestSender();
    createRegExp($senderId, ['is_validTransaction' => false]);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    expect(SMSRegularExp::findValidRegExp($senderId, $message))->toBeFalse();
});

test('findValidRegExp ignores regex from other sender', function () {
    $sender1 = createTestSender('bank1');
    $sender2 = createTestSender('bank2');
    createRegExp($sender1);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    expect(SMSRegularExp::findValidRegExp($sender2, $message))->toBeFalse();
});

// --- findInvalidRegExp ---

test('findInvalidRegExp returns match for invalid transaction regex', function () {
    $senderId = createTestSender();
    createRegExp($senderId, ['is_validTransaction' => false]);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    $result = SMSRegularExp::findInvalidRegExp($senderId, $message);
    expect($result)->not->toBeFalse();
});

test('findInvalidRegExp ignores valid transaction regex', function () {
    $senderId = createTestSender();
    createRegExp($senderId, ['is_validTransaction' => true]);

    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    expect(SMSRegularExp::findInvalidRegExp($senderId, $message))->toBeFalse();
});

// --- storeRegularExp ---

test('storeRegularExp creates new regex record', function () {
    $senderId = createTestSender();
    $regex = '/Purchase.*Amount:\s*(?P<currency>\w+)\s+(?P<amount>[\d,.]+).*At:\s*(?P<OtherAccountName>\w+).*Account:\s*(?P<MyAccountNumber>\d+)/s';
    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    SMSRegularExp::storeRegularExp(
        message: $message,
        regularExp: $regex,
        sender_id: $senderId,
        transactionType: 'withdrawal',
        ai_output: ['test' => true],
        isValid: true,
    );

    $record = SMSRegularExp::where('sender_id', $senderId)->first();
    expect($record)->not->toBeNull();
    expect($record->transactionType)->toBe('withdrawal');
    expect($record->regularExpMD5)->toBe(md5($regex));
    expect($record->is_validRegularExp)->toBeTrue();
    expect($record->is_validTransaction)->toBeTrue();
    expect($record->data['ai_output'])->toBe(['test' => true]);
});

test('storeRegularExp updates existing regex with same MD5', function () {
    $senderId = createTestSender();
    $regex = '/Purchase.*Amount:\s*(?P<currency>\w+)\s+(?P<amount>[\d,.]+).*At:\s*(?P<OtherAccountName>\w+).*Account:\s*(?P<MyAccountNumber>\d+)/s';
    $message = "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234";

    SMSRegularExp::storeRegularExp(
        message: $message,
        regularExp: $regex,
        sender_id: $senderId,
        transactionType: 'withdrawal',
    );

    SMSRegularExp::storeRegularExp(
        message: $message,
        regularExp: $regex,
        sender_id: $senderId,
        transactionType: 'deposit',
    );

    expect(SMSRegularExp::where('sender_id', $senderId)->count())->toBe(1);

    $record = SMSRegularExp::where('sender_id', $senderId)->first();
    expect($record->transactionType)->toBe('deposit');
});

test('storeRegularExp marks invalid regex when pattern does not match', function () {
    $senderId = createTestSender();
    $regex = '/NOMATCH(?P<amount>\d+)(?P<MyAccountNumber>\d+)(?P<OtherAccountName>\w+)/';
    $message = "This does not match at all";

    SMSRegularExp::storeRegularExp(
        message: $message,
        regularExp: $regex,
        sender_id: $senderId,
        transactionType: 'withdrawal',
    );

    $record = SMSRegularExp::where('sender_id', $senderId)->first();
    expect($record->is_validRegularExp)->toBeFalse();
});

// --- sender relationship ---

test('SMSRegularExp belongs to sender', function () {
    $senderId = createTestSender('mybank');
    $regExp = createRegExp($senderId);

    expect($regExp->sender)->toBeInstanceOf(SMSSender::class);
    expect($regExp->sender->sender)->toBe('mybank');
});
