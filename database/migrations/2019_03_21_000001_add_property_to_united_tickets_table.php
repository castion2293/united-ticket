<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPropertyToUnitedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('united_tickets', function ($table) {
            $table->text("property")->nullable()
                ->after("payout_at")
                ->comment('注單內容紀錄事項');
        });

        Schema::table('split_united_tickets', function ($table) {
            $table->text("property")->nullable()
                ->after("payout_at")
                ->comment('注單內容紀錄事項');
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
            $table->dropColumn("property");
        });

        Schema::table('split_united_tickets', function ($table) {
            $table->dropColumn("property");
        });
    }
}