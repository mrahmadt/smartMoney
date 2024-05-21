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
        Schema::create('average_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('attribute');
            $table->string('key');
            $table->integer('total');
            $table->integer('total_amount');
            $table->decimal('average_amount', 10, 2);
            $table->timestamps();
            $table->index(['type', 'attribute', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('average_transactions');
    }
};
