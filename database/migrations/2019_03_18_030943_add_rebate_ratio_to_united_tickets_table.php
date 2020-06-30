<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddRebateRatioToUnitedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('united_tickets', function ($table) {
            for($i = 0 ; $i < 13; $i++ ) {
                $table->decimal("depth_{$i}_rebate")->default(0)
                    ->after("depth_{$i}_ratio")
                    ->comment('退水占成比例');
            }
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
            for($i = 0 ; $i < 13; $i++ ) {
                $table->dropColumn("depth_{$i}_rebate");
            }
        });
    }
}