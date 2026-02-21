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
            $table->bigInteger('FF_account_id');
            $table->string('account_code')->nullable();
            $table->string('sms_sender')->nullable();
            $table->bigInteger('budget_id')->nullable();
            $table->boolean('sendTransactionAlert')->default(true);
            $table->foreignId('user_id')->nullable()->index();
            $table->boolean('defaultAccount')->default(false);
            $table->json('tags')->nullable();
            $table->json('values')->nullable();

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
