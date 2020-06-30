<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveRawTicketsRoyalAndChangeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_royal');
        Schema::dropIfExists('raw_tickets_royal_change');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('raw_tickets_royal', function (Blueprint $table) {
            $table->uuid('uuid');
        });
        Schema::create('raw_tickets_royal_change', function (Blueprint $table) {
            $table->uuid('uuid');
        });
    }
}