<?php

use App\Ai\Agents\SMSCategory;

// --- instructions ---

test('instructions returns non-empty string', function () {
    $agent = new SMSCategory();
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect(strlen($instructions))->toBeGreaterThan(100);
});

test('instructions contain category mapping hints', function () {
    $agent = new SMSCategory();
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Category mapping hints:');
    expect($instructions)->toContain('Cafe:');
    expect($instructions)->toContain('Groceries:');
    expect($instructions)->toContain('Shopping:');
    expect($instructions)->toContain('Dining:');
    expect($instructions)->toContain('Transportation:');
    expect($instructions)->toContain('Utilities:');
    expect($instructions)->toContain('Healthcare:');
    expect($instructions)->toContain('Entertainment:');
    expect($instructions)->toContain('Online Shopping:');
    expect($instructions)->toContain('Education:');
    expect($instructions)->toContain('Travel:');
    expect($instructions)->toContain('Personal Care:');
    expect($instructions)->toContain('Clothing:');
    expect($instructions)->toContain('Home & Furniture:');
    expect($instructions)->toContain('Government:');
    expect($instructions)->toContain('Insurance:');
    expect($instructions)->toContain('Charity:');
    expect($instructions)->toContain('Cash:');
    expect($instructions)->toContain('Transfer:');
});

test('instructions do not contain Subscriptions category', function () {
    $agent = new SMSCategory();
    expect($agent->instructions())->not->toContain('Subscriptions:');
});

test('instructions include streaming under Entertainment', function () {
    $agent = new SMSCategory();
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Netflix');
    expect($instructions)->toContain('Spotify');
    expect($instructions)->toContain('Disney+');
});

test('instructions include department stores under Shopping', function () {
    $agent = new SMSCategory();
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Target');
    expect($instructions)->toContain('Walmart');
    expect($instructions)->toContain('Costco');
});

test('instructions include pharmacies under Healthcare', function () {
    $agent = new SMSCategory();
    $instructions = $agent->instructions();

    expect($instructions)->toContain('CVS');
    expect($instructions)->toContain('Walgreens');
    expect($instructions)->toContain('Nahdi');
});

// --- default_categories ---

test('default_categories is empty array by default', function () {
    $agent = new SMSCategory();
    expect($agent->default_categories)->toBe([]);
});

test('default_categories can be overridden', function () {
    $agent = new SMSCategory();
    $agent->default_categories = ['Food', 'Transport'];
    expect($agent->default_categories)->toBe(['Food', 'Transport']);
});

// --- messages ---

test('messages returns empty iterable', function () {
    $agent = new SMSCategory();
    expect(iterator_to_array($agent->messages()))->toBe([]);
});

// --- tools ---

test('tools returns empty iterable', function () {
    $agent = new SMSCategory();
    expect(iterator_to_array($agent->tools()))->toBe([]);
});

// --- schema ---

test('schema returns error, category, and confidence fields', function () {
    $agent = new SMSCategory();

    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required', 'enum', 'description')->andReturnSelf();

    $schema = Mockery::mock(Illuminate\Contracts\JsonSchema\JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);

    $result = $agent->schema($schema);

    expect($result)->toHaveKeys(['error', 'category', 'confidence']);
});

test('schema category description excludes prefer clause when default_categories is empty', function () {
    $agent = new SMSCategory();
    $agent->default_categories = [];

    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required', 'enum', 'description')->andReturnSelf();

    $schema = Mockery::mock(Illuminate\Contracts\JsonSchema\JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);

    $result = $agent->schema($schema);
    expect($result)->toHaveKeys(['error', 'category', 'confidence']);
});

test('schema category description includes prefer clause when categories set', function () {
    $agent = new SMSCategory();
    $agent->default_categories = ['Food', 'Transport'];

    $descriptions = [];
    $fieldMock = Mockery::mock();
    $fieldMock->shouldReceive('required')->andReturnSelf();
    $fieldMock->shouldReceive('enum')->andReturnSelf();
    $fieldMock->shouldReceive('description')->andReturnUsing(function ($desc) use ($fieldMock, &$descriptions) {
        $descriptions[] = $desc;
        return $fieldMock;
    });

    $schema = Mockery::mock(Illuminate\Contracts\JsonSchema\JsonSchema::class);
    $schema->shouldReceive('string')->andReturn($fieldMock);

    $agent->schema($schema);

    $categoryDesc = $descriptions[1] ?? '';
    expect($categoryDesc)->toContain('Prefer:');
    expect($categoryDesc)->toContain('Food');
    expect($categoryDesc)->toContain('Transport');
});

// --- llm_model ---

test('llm_model is null by default', function () {
    $agent = new SMSCategory();
    expect($agent->llm_model)->toBeNull();
});

test('llm_model can be set', function () {
    $agent = new SMSCategory();
    $agent->llm_model = 'gpt-5';
    expect($agent->llm_model)->toBe('gpt-5');
});

// --- implements correct interfaces ---

test('implements Agent interface', function () {
    expect(new SMSCategory())->toBeInstanceOf(Laravel\Ai\Contracts\Agent::class);
});

test('implements Conversational interface', function () {
    expect(new SMSCategory())->toBeInstanceOf(Laravel\Ai\Contracts\Conversational::class);
});

test('implements HasStructuredOutput interface', function () {
    expect(new SMSCategory())->toBeInstanceOf(Laravel\Ai\Contracts\HasStructuredOutput::class);
});

test('implements HasTools interface', function () {
    expect(new SMSCategory())->toBeInstanceOf(Laravel\Ai\Contracts\HasTools::class);
});
