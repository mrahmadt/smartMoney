<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_category_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('firefly_transaction_id');
            $table->string('firefly_journal_id');
            $table->string('account_name');
            $table->foreignId('category_mapping_id')->constrained('category_mappings')->cascadeOnDelete();
            $table->foreignId('current_category_id')->constrained('categories')->cascadeOnDelete();
            $table->json('alternative_category_ids');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('budget_id')->nullable();
            $table->decimal('transaction_amount', 12, 2);
            $table->string('currency_code', 3)->nullable();
            $table->dateTime('transaction_date');
            $table->string('transaction_description');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('budget_id');
            $table->index('category_mapping_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_category_reviews');
    }
};
