<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smsregular_exps', function (Blueprint $table) {
            $table->dropColumn('stripNewLines');
        });
    }

    public function down(): void
    {
        Schema::table('smsregular_exps', function (Blueprint $table) {
            $table->boolean('stripNewLines')->default(false)->after('regularExpMD5');
        });
    }
};
