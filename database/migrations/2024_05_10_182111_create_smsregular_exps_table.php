<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smsregular_exps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sender_id')
                ->constrained('smssenders')
                ->cascadeOnDelete();

            $table->string('transactionType', 20);
            $table->text('regularExp');
            $table->string('regularExpMD5', 50);

            $table->boolean('stripNewLines')->default(false);

            $table->string('createdBy', 30)->nullable();

            $table->json('data')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_validTransaction')->default(true);
            $table->boolean('is_validRegularExp')->default(true);

            $table->timestamps();

            $table->index(['sender_id', 'is_active']);
            $table->unique(['sender_id', 'regularExpMD5']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smsregular_exps');
    }
};