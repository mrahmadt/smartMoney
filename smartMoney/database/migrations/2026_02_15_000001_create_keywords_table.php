<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255);
            $table->boolean('is_regularExp')->default(false);
            $table->string('replaceWith', 255)->nullable();
            $table->enum('keyword_type', ['phone', 'passcodes', 'misc', 'date', 'url', 'ignore', 'breaks', 'replace'])->default('ignore');
            $table->boolean('is_active')->default(true);
            $table->string('channel', 20)->default('sms');
            $table->timestamps();
            $table->index(['keyword_type', 'is_active', 'channel']);
            $table->index(['is_regularExp']);
            $table->unique(['keyword', 'keyword_type', 'is_regularExp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};