<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddGameResultToRawTicketsNineKLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_nine_k_lottery', function ($table) {
            $table->char('GameNum', 20)->nullable()
                ->after('GameTime')
                ->comment('遊戲期號');

            $table->string('GameResult')->nullable()
                ->after('GameTime')
                ->comment('開獎結果');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_nine_k_lottery', function ($table) {
            $table->dropColumn("GameNum");
            $table->dropColumn("GameResult");
        });
    }
}