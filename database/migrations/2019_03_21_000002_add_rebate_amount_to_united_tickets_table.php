<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddRebateAmountToUnitedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('united_tickets', function ($table) {
            $table->decimal('rebate_amount', 9, 2)->default(0)
                ->after("winnings")
                ->comment('系統退水值');
        });

        Schema::table('split_united_tickets', function ($table) {
            $table->decimal('rebate_amount', 9, 2)->default(0)
                ->after("winnings")
                ->comment('系統退水值');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('united_tickets', function ($table) {
            $table->dropColumn("rebate_amount");
        });

        Schema::table('split_united_tickets', function ($table) {
            $table->dropColumn("rebate_amount");
        });
    }
}