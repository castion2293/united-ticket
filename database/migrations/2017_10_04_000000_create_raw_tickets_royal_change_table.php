<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRawTicketsRoyalChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_royal_change', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            $table->string('WebId', 10)
                ->comment('站台代碼');

            $table->integer('Id')
                ->comment('[UK] 帳務流水號');

            $table->string('UserId', 20)
                ->comment('帳號');

            $table->string('UserName', 40)
                ->comment('名稱');

            $table->string('RoyalUserID', 40)
                ->comment('皇家玩家編號');

            $table->string('IP', 15)
                ->comment('登入IP');

            $table->dateTime('DateTime')
                ->comment('下注日期');

            $table->string('GameId', 20)
                ->comment('遊戲代碼');

            $table->string('ServerType', 20)
                ->comment('桌號');

            $table->string('JiTaiId', 20)
                ->comment('機台編號');

            $table->string('JiTaiNo', 20)
                ->comment('機台號碼');

            $table->string('NoRun', 20)
                ->comment('條號');

            $table->string('NoActive', 20)
                ->comment('輪號');

            $table->string('MaHao', 64)
                ->comment('碼號');

            $table->decimal('YaMa', 20, 4)
                ->comment('押碼量(有效押分、有效下注金額)');

            $table->decimal('StakeScore', 20, 4)
                ->comment('原下注金額');

            $table->decimal('WinLost', 20, 4)
                ->comment('輸贏');

            $table->decimal('Amount', 20, 4)
                ->comment('結果');

            $table->decimal('Odds', 20, 4)
                ->comment('賠率');

            $table->tinyInteger('Active')
                ->comment('下注狀態 3:取消 5:改單');

            $table->string('OpenPai', 64)
                ->comment('開牌結果');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['Id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_royal_change');
    }
}
