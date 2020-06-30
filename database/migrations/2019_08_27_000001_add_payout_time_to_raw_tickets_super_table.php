<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPayoutTimeToRawTicketsSuperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_super', function ($table) {
            $table->string('payout_time')->nullable()
                ->after('count_date')
                ->comment('結帳日期時間');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_super', function ($table) {
            $table->dropColumn("payout_time");
        });
    }
}