<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddCountDateToRawTicketsSuperLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_super_lottery', function ($table) {
            $table->string('count_date')->nullable()
                ->after('bet_time')
                ->comment('結帳日期');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_super_lottery', function ($table) {
            $table->dropColumn("count_date");
        });
    }
}
