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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('smssenders')->nullOnDelete();
            $table->json('shortcodes')->nullable();
            $table->string('firefly_account_name');
            $table->unsignedBigInteger('firefly_account_id')->unique();
            $table->string('currency_code', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
