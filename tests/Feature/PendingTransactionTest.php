<?php

use App\Models\PendingTransaction;
use App\Models\Setting;
use App\Models\SMS;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::set('parsesms_store_invalid_sms', 'true');
    Notification::fake();
    User::factory()->create(['id' => 1]);
});

// --- PendingTransaction Model ---

test('can create a pending transaction', function () {
    $sms = SMS::create([
        'sender' => 'TestBank',
        'message' => 'Test message for transaction',
        'message_hash' => SMS::generateHash('TestBank', 'Test message for transaction'),
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    $pending = PendingTransaction::create([
        'sms_id' => $sms->id,
        'reason' => 'manual_review',
        'type' => 'withdrawal',
        'amount' => 100.50,
        'currency' => 'SAR',
        'date' => now(),
        'description' => 'Test transaction',
        'source_account_name' => 'My Account',
        'destination_account_name' => 'Store',
    ]);

    expect($pending)->toBeInstanceOf(PendingTransaction::class)
        ->and($pending->reason)->toBe('manual_review')
        ->and($pending->type)->toBe('withdrawal')
        ->and((float) $pending->amount)->toBe(100.50);
});

test('pending transaction belongs to sms', function () {
    $sms = SMS::create([
        'sender' => 'TestBank',
        'message' => 'Test message',
        'message_hash' => SMS::generateHash('TestBank', 'Test message'),
        'content' => [],
        'is_valid' => true,
        'is_processed' => false,
    ]);

    $pending = PendingTransaction::create([
        'sms_id' => $sms->id,
        'reason' => 'error',
        'error_message' => 'Firefly rejected',
        'type' => 'deposit',
        'amount' => 500,
        'currency' => 'USD',
        'date' => now(),
    ]);

    expect($pending->sms->id)->toBe($sms->id);
});

test('pending transaction casts tags to array', function () {
    $pending = PendingTransaction::create([
        'reason' => 'manual_review',
        'type' => 'withdrawal',
        'amount' => 200,
        'currency' => 'SAR',
        'date' => now(),
        'tags' => ['regex:5', 'shortcode:1234'],
    ]);

    $pending->refresh();
    expect($pending->tags)->toBeArray()
        ->and($pending->tags)->toContain('regex:5');
});

test('pending transaction can be deleted', function () {
    $pending = PendingTransaction::create([
        'reason' => 'manual_review',
        'type' => 'withdrawal',
        'amount' => 100,
        'currency' => 'SAR',
        'date' => now(),
    ]);

    $id = $pending->id;
    $pending->delete();

    expect(PendingTransaction::find($id))->toBeNull();
});

test('pending transaction with error reason stores error message', function () {
    $pending = PendingTransaction::create([
        'reason' => 'error',
        'error_message' => 'Duplicate transaction hash',
        'type' => 'withdrawal',
        'amount' => 300,
        'currency' => 'SAR',
        'date' => now(),
    ]);

    expect($pending->reason)->toBe('error')
        ->and($pending->error_message)->toBe('Duplicate transaction hash');
});
