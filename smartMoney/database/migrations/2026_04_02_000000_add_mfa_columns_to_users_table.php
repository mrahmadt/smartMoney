<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mfa_secret')->nullable()->after('password');
            $table->json('mfa_recovery_codes')->nullable()->after('mfa_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_recovery_codes']);
        });
    }
};
