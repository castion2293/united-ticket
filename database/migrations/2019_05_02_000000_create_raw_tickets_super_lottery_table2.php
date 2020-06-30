<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSuperLotteryTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_super_lottery');

        Schema::create('raw_tickets_super_lottery', function (Blueprint $table) {
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
            $table->integer('state')->nullable()
                ->comment('開獎狀態 0:未開講 1:已開獎');

            $table->string('name')->nullable()
                ->comment('期數名稱');

            $table->string('lottery')->nullable()
                ->comment('開獎號碼');

            $table->string('bet_no')->nullable()
                ->comment('下注編號');

            $table->string('bet_time')->nullable()
                ->comment('投注時間');

            $table->string('account')->nullable()
                ->comment('下注帳號');

            $table->integer('game_id')->nullable()
                ->comment('遊戲類型');

            $table->string('game_type')->nullable()
                ->comment('玩法類型編號');

            $table->string('bet_type')->nullable()
                ->comment('玩法編號');

            $table->string('detail')->nullable()
                ->comment('下注內容');

            $table->string('cmount')->nullable()
                ->comment('下注金額');

            $table->string('gold')->nullable()
                ->comment('中獎金額');

            $table->string('odds')->nullable()
                ->comment('賠率');

            $table->string('retake')->nullable()
                ->comment('退水金額');

            $table->integer('status')->nullable()
                ->comment('注單狀態(0:有效單 1:已刪除');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('uuid');
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
        Schema::dropIfExists('raw_tickets_super_lottery');
    }
}