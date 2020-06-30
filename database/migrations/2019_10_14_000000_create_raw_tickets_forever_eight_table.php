<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsForeverEightTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_forever_eight');

        Schema::create('raw_tickets_forever_eight', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            // === 各遊戲站的注單欄位 ===
            $table->string('BillNo')->nullable()
                ->comment('细单编号');

            $table->string('GameID')->nullable()
                ->comment('游戏编号');

            $table->float('BetValue',10,4)->nullable()
                ->comment('注额');

            $table->float('NetAmount',10,4)->nullable()
                ->comment('赢得');

            $table->dateTime('SettleTime')->nullable()
                ->comment('结算时间');

            $table->string('AgentsCode')->nullable()
                ->comment('代理商编号');

            $table->string('Account')->nullable()
                ->comment('会员账号');

            $table->string('TicketStatus')->nullable()
                ->comment('游戏结果');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('Account');
            $table->index('BillNo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_forever_eight');
    }
}