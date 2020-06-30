<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsHabaneroTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_habanero');

        Schema::create('raw_tickets_habanero', function (Blueprint $table) {
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

            $table->char('Username', 191)->nullable()
                ->comment('會員帳號');

            $table->char('PlayerId', 191)->nullable()
                ->comment('會員ID');

            $table->char('BrandGameId', 191)->nullable()
                ->commnet('代理ID');

            $table->char('GameName', 191)->nullable()
                ->commnet('遊戲名稱');

            $table->char('GameKeyName', 191)->nullable()
                ->comment('遊戲代碼名稱');

            $table->char('GameInstanceId', 191)->nullable()
                ->comment('遊戲實例編號');

            $table->char('FriendlyGameInstanceId', 191)->nullable()
                ->comment('友好遊戲實例ID');

            $table->decimal('Stake', 17, 2)->nullable()
                ->comment('总投注');

            $table->decimal('Payout', 17, 2)->nullable()
                ->comment('總輸贏(包括奖池赢得)');

            $table->decimal('JackpotWin', 17, 2)->nullable()
                ->comment('赢得游戏奖池的价值');

            $table->decimal('JackpotContribution', 17, 2)->nullable()
                ->comment('下注金额促成所有活动的最高奖金');

            $table->char('GameStateId', 10)->nullable()
                ->comment('注單狀態');

            $table->char('GameStateName', 191)->nullable()
                ->comment('注單狀態的描述');

            $table->char('GameTypeId', 191)->nullable()
                ->comment('游戏种类识别码');

            $table->dateTime('DtStart')->nullable()
                ->comment('游戏开始日期');

            $table->dateTime('DtCompleted')->nullable()
                ->comment('游戏结束日期(null 如果未完成)');

            $table->decimal('BalanceAfter', 17, 2)->nullable()
                ->comment('玩家在游戏结束时的余额(如果游戏未完成，则为 NULL)');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('GameInstanceId');
            $table->index('Username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_habanero');
    }
}
