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
        Schema::table('smses', function (Blueprint $table) {
            $table->string('message_hash', 32)->nullable()->after('message')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smses', function (Blueprint $table) {
            $table->dropColumn('message_hash');
        });
    }
};
