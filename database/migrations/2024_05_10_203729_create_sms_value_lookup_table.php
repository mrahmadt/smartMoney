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
        Schema::create('parseSMS_value_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value');
            $table->string('replaceWith');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parseSMS_value_lookup');
    }
};
