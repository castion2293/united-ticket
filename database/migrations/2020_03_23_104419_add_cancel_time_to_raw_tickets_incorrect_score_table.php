<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCancelTimeToRawTicketsIncorrectScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_incorrect_score', function ($table) {
            $table->char('cancelTime', 20)->nullable()
                ->after('betTime')
                ->comment('取消注單时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_incorrect_score', function ($table) {
            $table->dropColumn("cancelTime");
        });
    }
}
