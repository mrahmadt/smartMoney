<?php

use App\Ai\Agents\parseSMS;
use App\Models\Setting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;

uses(RefreshDatabase::class);

// --- instructions ---

test('instructions returns non-empty string', function () {
    $agent = new parseSMS;
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect(strlen($instructions))->toBeGreaterThan(100);
});

test('instructions contain SMS format examples', function () {
    $agent = new parseSMS;
    $instructions = $agent->instructions();

    expect($instructions)->toContain('POS/Card purchases');
    expect($instructions)->toContain('Transfers');
    expect($instructions)->toContain('ATM withdrawals');
    expect($instructions)->toContain('Deposits');
    expect($instructions)->toContain('Bill payments');
    expect($instructions)->toContain('Multi-currency');
});

test('instructions contain extraction rules', function () {
    $agent = new parseSMS;
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Account identifiers may be masked');
    expect($instructions)->toContain('Amounts may include commas');
    expect($instructions)->toContain('Do not confuse account balance');
    expect($instructions)->toContain('Non-transactional SMS');
});

test('instructions loads from Setting when available', function () {
    Setting::set('parsesms_prompt', 'Custom parser prompt');

    $agent = new parseSMS;
    expect($agent->instructions())->toBe('Custom parser prompt');
});

test('instructions falls back to default when Setting is empty', function () {
    $agent = new parseSMS;
    $instructions = $agent->instructions();

    expect($instructions)->toContain('bank SMS transaction parser');
});

// --- messages ---

test('messages returns empty iterable', function () {
    $agent = new parseSMS;
    expect(iterator_to_array($agent->messages()))->toBe([]);
});

// --- tools ---

test('tools returns empty iterable', function () {
    $agent = new parseSMS;
    expect(iterator_to_array($agent->tools()))->toBe([]);
});

// --- schema ---

test('schema returns all required transaction fields', function () {
    $agent = new parseSMS;

    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required', 'enum', 'description', 'pattern')->andReturnSelf();

    $schema = Mockery::mock(JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);
    $schema->shouldReceive('number')->andReturn($fieldMock);

    $result = $agent->schema($schema);

    expect($result)->toHaveKeys([
        'error',
        'transactionType',
        'amount',
        'currency',
        'totalAmount',
        'totalAmountCurrency',
        'fees',
        'feesCurrency',
        'transactionDateTime',
        'sourceAccountNumber',
        'sourceAccountName',
        'destinationAccountNumber',
        'destinationAccountName',
    ]);
});

test('schema does not include category field', function () {
    $agent = new parseSMS;

    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required', 'enum', 'description', 'pattern')->andReturnSelf();

    $schema = Mockery::mock(JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);
    $schema->shouldReceive('number')->andReturn($fieldMock);

    $result = $agent->schema($schema);

    expect($result)->not->toHaveKey('category');
});

test('schema has exactly 13 fields', function () {
    $agent = new parseSMS;

    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required', 'enum', 'description', 'pattern')->andReturnSelf();

    $schema = Mockery::mock(JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);
    $schema->shouldReceive('number')->andReturn($fieldMock);

    $result = $agent->schema($schema);

    expect(count($result))->toBe(13);
});

// --- implements correct interfaces ---

test('implements Agent interface', function () {
    expect(new parseSMS)->toBeInstanceOf(Agent::class);
});

test('implements Conversational interface', function () {
    expect(new parseSMS)->toBeInstanceOf(Conversational::class);
});

test('implements HasStructuredOutput interface', function () {
    expect(new parseSMS)->toBeInstanceOf(HasStructuredOutput::class);
});

test('implements HasTools interface', function () {
    expect(new parseSMS)->toBeInstanceOf(HasTools::class);
});

// --- no default_categories property ---

test('does not have default_categories property', function () {
    $agent = new parseSMS;
    expect(property_exists($agent, 'default_categories'))->toBeFalse();
});
