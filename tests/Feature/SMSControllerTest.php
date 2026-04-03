<?php

use App\Models\SMS;
use App\Models\SMSSender;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validSMSPayload($overrides = []): array
{
    $base = [
        '_version' => 1,
        'query' => [
            'sender' => 'TestBank',
            'message' => [
                'text' => "Purchase\nAmount: SAR 100.00\nAt: STARBUCKS\nAccount: 1234\nDate: 2024-01-24 12:40:23",
            ],
        ],
        'app' => ['version' => '1.1'],
    ];

    return array_replace_recursive($base, $overrides);
}

beforeEach(function () {
    Setting::set('parsesms_enabled', 'true');
    Setting::set('parsesms_store_invalid_sms', 'true');
    Setting::set('parsesms_min_sms_length', '10');

    DB::table('smssenders')->insert(['sender' => 'testbank', 'is_active' => true]);
});

test('returns disabled error when parseSMS is disabled', function () {
    Setting::set('parsesms_enabled', 'false');

    $response = $this->postJson('/api/sms/filter', validSMSPayload());

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'disabled']);
});

test('returns noData error when sender is missing', function () {
    $response = $this->postJson('/api/sms/filter', [
        'query' => ['message' => ['text' => 'hello']],
    ]);

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'noData']);
});

test('returns noData error when message text is missing', function () {
    $response = $this->postJson('/api/sms/filter', [
        'query' => ['sender' => 'TestBank'],
    ]);

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'noData']);
});

test('returns invalidSender error for unknown sender', function () {
    $response = $this->postJson('/api/sms/filter', validSMSPayload([
        'query' => ['sender' => 'UnknownBank'],
    ]));

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'invalidSender']);
});

test('returns duplicate error for duplicate SMS', function () {
    $payload = validSMSPayload();
    $sender = $payload['query']['sender'];
    $message = $payload['query']['message']['text'];

    SMS::create([
        'sender' => strtolower($sender),
        'message' => $message,
        'message_hash' => SMS::generateHash($sender, $message),
        'content' => $payload,
        'is_valid' => true,
        'is_processed' => false,
    ]);

    $response = $this->postJson('/api/sms/filter', $payload);

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'duplicate']);
});

test('returns invalidSMS error for non-transaction SMS', function () {
    $payload = validSMSPayload([
        'query' => ['message' => ['text' => 'Welcome to our bank. Thank you for joining us.']],
    ]);

    $response = $this->postJson('/api/sms/filter', $payload);

    $response->assertStatus(200)
        ->assertJson(['filter' => true, 'error' => 'invalidSMS']);
});

test('stores invalid SMS when store_invalid_sms is enabled', function () {
    Setting::set('parsesms_store_invalid_sms', 'true');

    $payload = validSMSPayload([
        'query' => ['message' => ['text' => 'Welcome to our bank. Thank you for joining us.']],
    ]);

    $this->postJson('/api/sms/filter', $payload);

    $this->assertDatabaseHas('smses', [
        'is_valid' => false,
        'is_processed' => true,
    ]);

    $sms = SMS::first();
    expect($sms->message_hash)->not->toBeNull();
});

test('does not store invalid SMS when store_invalid_sms is disabled', function () {
    Setting::set('parsesms_store_invalid_sms', 'false');

    $payload = validSMSPayload([
        'query' => ['message' => ['text' => 'Welcome to our bank. Thank you for joining us.']],
    ]);

    $this->postJson('/api/sms/filter', $payload);

    $this->assertDatabaseCount('smses', 0);
});

test('stores valid SMS with message_hash and dispatches job', function () {
    Queue::fake();

    $payload = validSMSPayload();

    $response = $this->postJson('/api/sms/filter', $payload);

    $response->assertStatus(200)
        ->assertJson(['filter' => true]);
    $response->assertJsonMissing(['error']);

    $this->assertDatabaseHas('smses', [
        'sender' => 'testbank',
        'is_valid' => true,
        'is_processed' => false,
    ]);

    $sms = SMS::where('is_valid', true)->first();
    expect($sms->message_hash)->toBe(SMS::generateHash($payload['query']['sender'], $sms->message));

    Queue::assertPushed(\App\Jobs\parseSMSJob::class);
});

test('generates consistent hash for same sender and message', function () {
    $hash1 = SMS::generateHash('TestBank', 'Hello World');
    $hash2 = SMS::generateHash('TestBank', 'Hello World');
    $hash3 = SMS::generateHash('testbank', 'Hello World');

    expect($hash1)->toBe($hash2);
    expect($hash1)->toBe($hash3); // case-insensitive sender
});

test('generates different hash for different messages', function () {
    $hash1 = SMS::generateHash('TestBank', 'Message A');
    $hash2 = SMS::generateHash('TestBank', 'Message B');

    expect($hash1)->not->toBe($hash2);
});

test('second identical SMS is rejected as duplicate', function () {
    Queue::fake();

    $payload = validSMSPayload();

    $response1 = $this->postJson('/api/sms/filter', $payload);
    $response1->assertStatus(200)->assertJsonMissing(['error']);

    $response2 = $this->postJson('/api/sms/filter', $payload);
    $response2->assertStatus(200)->assertJson(['error' => 'duplicate']);

    $this->assertDatabaseCount('smses', 1);
});
