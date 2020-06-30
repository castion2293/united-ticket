<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsAllBetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_all_bet', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            $table->string('username')
                ->comment('客戶帳號');

            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->string('client')->nullable()
                ->comment('客戶用戶名');

            $table->bigInteger('betNum')->nullable()
                ->comment('[UK]注單編號');

            $table->string('gameRoundId')->nullable()
                ->comment('遊戲局編號');

            $table->integer('gameType')->nullable()
                ->comment('遊戲類型');

            $table->dateTime('betTime')->nullable()
                ->comment('投注時間');

            $table->decimal('betAmount', 10, 2)->nullable()
                ->comment('投注金額');

            $table->decimal('validAmount', 10, 2)->nullable()
                ->comment('有效投注金額');

            $table->decimal('winOrLoss', 10, 2)->nullable()
                ->comment('輸贏金額');

            $table->tinyInteger('state')->nullable()
                ->comment('注單狀態(0:正常 1:不正常)');

            $table->integer('betType')->nullable()
                ->comment('投注類型');

            $table->text('gameResult')->nullable()
                ->comment('開牌結果');

            $table->dateTime('gameRoundEndTime')->nullable()
                ->comment('遊戲結束時間');

            $table->dateTime('gameRoundStartTime')->nullable()
                ->comment('遊戲開始時間');

            $table->string('tableName')->nullable()
                ->comment('桌台名稱');

            $table->integer('commission')->nullable()
                ->comment('桌台類型 (100:非免佣 1:免佣)');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['betNum']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_all_bet');
    }
}
