<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddFlagToUnitedTicketsTable extends Migration
{
    private $sTable = 'united_tickets';
    private $sColumn = 'flag';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->sTable, function ($table) {
            $table->text($this->sColumn)->nullable()
                ->after('payout_at')
                ->comment('動態紀錄內容欄位');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->sTable, function ($table) {
            $table->dropColumn($this->sColumn);
        });
    }
}