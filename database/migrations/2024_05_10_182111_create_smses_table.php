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
        Schema::create('smses', function (Blueprint $table) {
            $table->id();
            $table->string('sender');
            $table->text('message');
            $table->json('content')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_processed')->default(false);
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smses');
    }
};
