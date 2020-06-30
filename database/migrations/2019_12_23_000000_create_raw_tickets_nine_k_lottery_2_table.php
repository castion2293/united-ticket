<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsNineKLottery2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_nine_k_lottery_2');

        Schema::create('raw_tickets_nine_k_lottery_2', function (Blueprint $table) {
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
            $table->string('BossID')->nullable()
                ->comment('代理商帳號');

            $table->string('MemberAccount')->nullable()
                ->comment('會員帳號');

            $table->string('TypeCode')->nullable()
                ->comment('遊戲代碼');

            $table->string('GameDate')->nullable()
                ->comment('帳務日期');

            $table->string('GameTime')->nullable()
                ->comment('帳務時間');

            $table->char('GameNum', 20)->nullable()
                ->comment('遊戲期號');

            $table->string('GameResult')->nullable()
                ->comment('開獎結果');

            $table->integer('WagerID')->nullable()
                ->comment('注單唯一單號');

            $table->string('WagerDate')->nullable()
                ->comment('投注時間');

            $table->text('BetItem')->nullable()
                ->comment('投注項目');

            $table->integer('TotalAmount')->nullable()
                ->comment('投注金額');

            $table->integer('BetAmount')->nullable()
                ->comment('有效投注金額');

            $table->string('PayOff')->nullable()
                ->comment('派彩金額');

            $table->char('Result', 2)->nullable()
                ->comment('注單狀態 (W:贏 L:輸 X: 未派彩 C:註銷)');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('WagerID');
            $table->index('MemberAccount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_nine_k_lottery_2');
    }
}