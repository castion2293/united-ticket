<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPlayingScoreToRawTicketsSuperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_super', function ($table) {
            $table->string('playing_score')->nullable()
                ->after('fashion')
                ->comment('足球基準分 比賽進行比分(客-主) 沒有比分為預設 0-0');
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
            $table->dropColumn("playing_score");
        });
    }
}