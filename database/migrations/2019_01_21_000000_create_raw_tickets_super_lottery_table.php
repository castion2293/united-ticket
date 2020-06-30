<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSuperLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
            $table->string('level')->nullable()
                ->comment('會員級別');

            $table->string('account')->nullable()
                ->comment('帳號');

            $table->string('Bet_date')->nullable()
                ->comment('日期');

            $table->string('game_id')->nullable()
                ->comment('遊戲 ID (11:六合,12:大樂,13:539)');

            $table->string('ccount')->nullable()
                ->comment('投注數量');

            $table->string('cmount')->nullable()
                ->comment('投注金額');

            $table->string('bmount')->nullable()
                ->comment('有效投注');

            $table->string('m_gold')->nullable()
                ->comment('會員中獎金額');

            $table->string('m_rake')->nullable()
                ->comment('會員退水');

            $table->string('m_result')->nullable()
                ->comment('結果會員');

            $table->integer('up_no1_result')->nullable()
                ->comment('結果代理');

            $table->integer('up_no2_result')->nullable()
                ->comment('結果總代理');

            $table->integer('up_no1_rake')->nullable()
                ->comment('退水代理');

            $table->string('up_no2_rake')->nullable()
                ->comment('退水總代理');

            $table->string('up_no1')->nullable()
                ->comment('代理');

            $table->string('up_no2')->nullable()
                ->comment('總代理');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['uuid']);
            // 指定查詢索引
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
