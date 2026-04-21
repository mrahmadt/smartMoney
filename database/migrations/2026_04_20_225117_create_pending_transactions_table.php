<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_id')->nullable()->constrained('smses')->nullOnDelete();
            $table->string('reason'); // manual_review, error
            $table->text('error_message')->nullable();
            $table->string('type'); // withdrawal, deposit, transfer
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->dateTime('date');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('category_name')->nullable();
            $table->integer('source_account_id')->nullable();
            $table->string('source_account_name')->nullable();
            $table->integer('destination_account_id')->nullable();
            $table->string('destination_account_name')->nullable();
            $table->json('tags')->nullable();
            $table->integer('budget_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_transactions');
    }
};
