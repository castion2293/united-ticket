<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsHyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_hy', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            $table->string('UserAccount', 30)
                ->comment('會員帳號');

            $table->string('AgentAccount', 30)
                ->comment('代理帳號');

            $table->integer('ParentUserID')
                ->comment('上級ID');

            $table->dateTime('BettingDate')
                ->comment('下注時間');

            $table->bigInteger('BettingNO')
                ->comment('注單號');

            $table->string('BettingID', 40)
                ->comment('[UK] 投注ID');

            $table->decimal('BettingCredits', 18, 2)
                ->comment('投注積分');

            $table->decimal('PreCreditsPoint', 18, 2)
                ->comment('投注前積分');

            $table->decimal('AfterPayoutCredits', 18, 2)
                ->comment('派彩積分');

            $table->decimal('WashCodeCredits', 18, 2)
                ->comment('洗碼積分');

            $table->decimal('WinningCredits', 18, 2)
                ->comment('輸贏積分');

            $table->string('GameResult', 64)
                ->comment('遊戲結果');

            $table->string('GameRoomName', 20)
                ->comment('遊戲大廳');

            $table->string('GamblingCode', 32)
                ->comment('賭局號');

            $table->tinyInteger('GameType')
                ->comment('遊戲類型');

            $table->string('DealerName', 12)
                ->comment('荷官');

            $table->string('GameName', 6)
                ->comment('遊戲');

            $table->string('SetGameNo', 20)
                ->comment('靴號局號');

            $table->tinyInteger('IsPayout')
                ->comment('是否派彩 0:未派彩 1:已派彩');

            $table->string('BettingPoint', 64)
                ->comment('下注點');

            $table->string('TableName', 20)
                ->comment('桌號');

            $table->dateTime('UpdatedDate')
                ->comment('更新時間');

            $table->string('TrackIP', 25)
                ->comment('投注IP');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['BettingID']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_hy');
    }
}
