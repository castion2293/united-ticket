<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHandlingfeeRawTicketsIncorrectScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_incorrect_score', function ($table) {
            $table->decimal('handlingFee', 18, 2)->nullable()
                ->after('validBetAmount')
                ->comment('手續費5%');
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
            $table->dropColumn("handlingFee");
        });
    }
}
