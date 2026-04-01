<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_mappings', function (Blueprint $table) {
            $table->json('alternative_category_ids')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_mappings', function (Blueprint $table) {
            $table->dropColumn('alternative_category_ids');
        });
    }
};
