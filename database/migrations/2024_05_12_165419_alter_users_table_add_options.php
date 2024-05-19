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
            $table->string('budgets')->nullable();
            $table->boolean('accessAllBudgets')->default(false);
            $table->string('accounts')->nullable();
            $table->boolean('accessAllAccounts')->default(false);
            $table->boolean('alertViaEmail')->default(true);
            $table->boolean('alertNewBillCreation')->default(true);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function(Blueprint $table){
            $table->dropColumn('budgets');
            $table->dropColumn('accessAllBudgets');
            $table->dropColumn('accounts');
            $table->dropColumn('accessAllAccounts');
            $table->dropColumn('alertViaEmail');
            $table->dropColumn('alertNewBillCreation');
        });
    }
};
