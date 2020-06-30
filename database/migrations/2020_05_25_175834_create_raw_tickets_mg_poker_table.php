<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRawTicketsMgPokerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_mg_poker', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');
            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');
            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->integer('gameId')->nullable()
                ->comment('遊戲Id');

            $table->string('account')->nullable()
                ->comment('玩家帳號');

            $table->integer('accountId')->nullable()
                ->comment('玩家帳號id');

            $table->string('platform')->nullable()
                ->comment('備註App/PC/WAP');

            $table->string('roundId')->nullable()
                ->comment('局號(唯一)');

            $table->integer('fieldId')->nullable()
                ->comment('場id');

            $table->string('filedName')->nullable()
                ->comment('場名稱');

            $table->integer('tableId')->nullable()
                ->comment('桌子id');

            $table->integer('chair')->nullable()
                ->comment('座位號');

            $table->float('bet', 10, 2)->nullable()
                ->comment('下注金額');

            $table->float('validBet', 10, 2)->nullable()
                ->comment('有效下注');

            $table->float('win', 10, 2)->nullable()
                ->comment('派獎金額');

            $table->float('lose', 10, 2)->nullable()
                ->comment('盈虧金額');

            $table->float('fee', 10, 2)->nullable()
                ->comment('服務費');

            $table->float('enterMoney', 10, 2)->nullable()
                ->comment('遊戲初始金額');

            $table->string('createTime')->nullable()
                ->comment('創建時間');

            $table->string('roundBeginTime')->nullable()
                ->comment('遊戲開始時間');

            $table->string('roundEndTime')->nullable()
                ->comment('遊戲結束時間');

            $table->string('ip')->nullable()
                ->comment('ip');


            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('roundId');
            $table->index('account');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_mg_poker');
    }
}
