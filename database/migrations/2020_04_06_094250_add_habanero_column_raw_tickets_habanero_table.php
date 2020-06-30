<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHabaneroColumnRawTicketsHabaneroTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_tickets_habanero', function ($table) {
            $table->char('BrandId', 191)->nullable()
                ->after('PlayerId')
                ->comment('代理商ID');
            $table->char('CurrencyCode', 20)->nullable()
                ->after('JackpotContribution')
                ->comment('幣別');
            $table->integer('ChannelTypeId')->nullable()
                ->after('JackpotContribution')
                ->comment('查看頻道的型態');
            $table->renameColumn('DtStart', 'DtStarted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_tickets_habanero', function ($table) {
            $table->renameColumn('DtStarted', 'DtStart');
            $table->dropColumn("BrandId");
            $table->dropColumn("CurrencyCode");
            $table->dropColumn("ChannelTypeId");
        });
    }
}
