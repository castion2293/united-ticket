<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddEvtidToRawTicketsIncorrectScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_incorrect_score', function ($table) {
            $table->integer('evtid')->nullable()
                ->after('refNo')
                ->comment('賽事編號');
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
            $table->dropColumn("evtid");
        });
    }
}
