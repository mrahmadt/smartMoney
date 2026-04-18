<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smses', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('message_hash')->index();
        });
    }

    public function down(): void
    {
        Schema::table('smses', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
