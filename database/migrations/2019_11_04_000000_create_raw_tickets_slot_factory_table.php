<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSlotFactoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_slot_factory');

        Schema::create('raw_tickets_slot_factory', function (Blueprint $table) {
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
            $table->char('TransactionID', 50)->nullable()
                ->comment('單號');

            $table->char('AccountID', 32)->nullable()
                ->comment('會員帳號');

            $table->char('RoundID', 50)->nullable()
                ->comment('局號');

            $table->char('GameName', 32)->nullable()
                ->comment('遊戲名稱');

            $table->char('SpinDate', 32)->nullable()
                ->comment('下注日期');

            $table->char('Currency', 5)->nullable()
                ->comment('幣別');

            $table->integer('Lines')->nullable()
                ->comment('線數');

            $table->char('LineBet', 32)->nullable()
                ->comment('線數下注');

            $table->char('TotalBet', 32)->nullable()
                ->comment('總下注');

            $table->char('CashWon', 32)->nullable()
                ->comment('總派彩');

            $table->boolean('GambleGames')->nullable()
                ->comment('是否是gamble games');

            $table->boolean('FreeGames')->nullable()
                ->comment('是否是free games');

            $table->integer('FreeGamePlayed')->nullable()
                ->comment('免費遊戲已玩局數');

            $table->integer('FreeGameRemaining')->nullable()
                ->comment('免費遊戲已玩局數');

            $table->char('Type', 32)->nullable()
                ->commnet('免費遊戲類型');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('AccountID');
            $table->index('TransactionID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_slot_factory');
    }
}
