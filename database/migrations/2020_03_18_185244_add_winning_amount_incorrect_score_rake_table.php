<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWinningAmountIncorrectScoreRakeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incorrect_score_rakes', function ($table) {
            $table->decimal('winningAmount', 18, 2)->nullable()
                ->after('rake')
                ->comment('贏額');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incorrect_score_rakes', function ($table) {
            $table->dropColumn("winningAmount");
        });
    }
}
