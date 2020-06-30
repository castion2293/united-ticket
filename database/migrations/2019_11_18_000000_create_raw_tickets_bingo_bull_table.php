<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsBingoBullTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_bingo_bull');

        Schema::create('raw_tickets_bingo_bull', function (Blueprint $table) {
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
            $table->char('status', 1)->nullable()
                ->comment('注單狀態 0:未結 1:已結');

            $table->char('betNo', 30)->nullable()
                ->comment('單號');

            $table->text('betData')->nullable()
                ->comment('投注項目');

            $table->decimal('realBetMoney', 10, 2)->nullable()
                ->comment('下注點數');

            $table->char('openNo', 15)->nullable()
                ->comment('期號');

            $table->decimal('okMoney', 10, 2)->nullable()
                ->comment('輸贏值');

            $table->decimal('totalMoney', 10, 2)->nullable()
                ->comment('結算');

            $table->decimal('pumpMoney', 10, 2)->nullable()
                ->comment('抽水點數');

            $table->datetime('reportTime')->nullable()
                ->comment('結算日期');

            $table->datetime('createTime')->nullable()
                ->comment('結帳時間');

            $table->char('userType', 1)->nullable()
                ->comment('1 當莊/0 當閒');

            $table->char('account', 30)->nullable()
                ->comment('玩家帳號');

            $table->char('roomCode', 10)->nullable()
                ->comment('房間代碼');

            $table->integer('coin')->nullable()
                ->comment('房間幣值');

            $table->char('mainGame', 30)->nullable()
                ->comment('遊戲名稱');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('betNo');
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
        Schema::dropIfExists('raw_tickets_bingo_bull');
    }
}