<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsHoldemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_holdem', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            $table->integer('PlatformID')
                ->comment('平台代號');

            $table->string('MemberAccount', 30)
                ->comment('[UK] 玩家帳號');

            $table->dateTime('PlayTime')
                ->comment('遊戲時間');

            $table->string('RoundCode', 40)
                ->comment('[UK] 遊戲將號');

            $table->integer('RoundId')
                ->comment('遊戲局數');

            $table->decimal('OriginalPoints', 10, 4)
                ->comment('原本點數');

            $table->decimal('Bet', 10, 4)
                ->comment('下注點數');

            $table->decimal('WinLose', 10, 4)
                ->comment('輸贏點數');

            $table->decimal('LastPoints', 10, 4)
                ->comment('輸贏後點數');

            $table->decimal('ServicePoints', 10, 4)
                ->comment('公點/水錢');

            $table->decimal('HandselServicePoints', 10, 4)
                ->comment('彩金公點');

            $table->string('GSLogPath', 100)
                ->comment('遊戲紀錄檔URL');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['MemberAccount', 'RoundCode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_holdem');
    }
}
