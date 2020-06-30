<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRawTicketsKkLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_kk_lottery', function ($table) {
            $table->renameColumn('id', 'bet_id');
            $table->renameColumn('lottery_cnname', 'lottery_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_kk_lottery', function ($table) {
            $table->renameColumn('bet_id', 'id');
            $table->renameColumn('lottery_name', 'lottery_cnname');
        });
    }
}

