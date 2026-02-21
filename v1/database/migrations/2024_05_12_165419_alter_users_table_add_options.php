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
        Schema::table('users', function(Blueprint $table)
        {
            $table->json('budgets')->nullable();
            $table->boolean('alertViaEmail')->default(true);
            $table->boolean('alertNewBillCreation')->default(true);
            $table->boolean('alertBillOverAmountPercentage')->default(true);
            $table->boolean('alertAbnormalTransaction')->default(true);
            $table->boolean('is_admin')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function(Blueprint $table){
            $table->dropColumn('budgets');
            $table->dropColumn('alertViaEmail');
            $table->dropColumn('alertNewBillCreation');
            $table->dropColumn('alertBillOverAmountPercentage');
            $table->dropColumn('alertAbnormalTransaction');
            $table->dropColumn('is_admin');
        });
    }
};
