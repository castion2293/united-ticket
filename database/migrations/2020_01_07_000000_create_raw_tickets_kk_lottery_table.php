<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsKkLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_kk_lottery');

        Schema::create('raw_tickets_kk_lottery', function (Blueprint $table) {
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
            $table->char('id', 30)->nullable()
                ->comment('訂單號');

            $table->char('platform_id', 30)->nullable()
                ->comment('平台ID');

            $table->char('user_id', 30)->nullable()
                ->comment('用戶ID');

            $table->char('user_name', 20)->nullable()
                ->comment('用戶帳號');

            $table->char('issue_code', 20)->nullable()
                ->comment('獎期號');

            $table->char('issue_seq', 20)->nullable()
                ->comment('獎期序號');

            $table->char('lottery_id', 20)->nullable()
                ->comment('彩種ID');

            $table->char('lottery_cnname', 30)->nullable()
                ->comment('彩種中文名');

            $table->char('lottery_enname', 30)->nullable()
                ->comment('彩種英文名');

            $table->char('method_id', 20)->nullable()
                ->comment('玩法ID');

            $table->char('method_name', 30)->nullable()
                ->comment('玩法名稱');

            $table->char('modes', 2)->nullable()
                ->comment('模式:0圓 1角 2分 3哩');

            $table->char('method_code', 20)->nullable()
                ->comment('玩法code');

            $table->char('bet_count', 10)->nullable()
                ->comment('注數');

            $table->char('single_price', 20)->nullable()
                ->comment('單價');

            $table->char('multiple', 10)->nullable()
                ->comment('倍數');

            $table->char('total_money', 20)->nullable()
                ->comment('投注總金額,單位元');

            $table->char('user_bonus_group', 10)->nullable()
                ->comment('用戶獎金組');

            $table->char('win_price', 20)->nullable()
                ->comment('中獎金額');

            $table->char('winning_status', 2)->nullable()
                ->comment('中獎狀態(0:未中獎 1:已中獎)');

            $table->char('cancel_status', 2)->nullable()
                ->comment('撤單狀態(0:未撤單 1:已撤單)');

            $table->char('open_lottery_status', 2)->nullable()
                ->comment('開獎狀態(0:未開獎 1:已開獎)');

            $table->string('bet_number')->nullable()
                ->comment('投注內容');

            $table->string('issue_winning_code')->nullable()
                ->comment('開獎號碼');

            $table->dateTime('create_time')->nullable()
                ->comment('建立注單時間');

            $table->dateTime('modify_time')->nullable()
                ->comment('修改注單時間');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('id');
            $table->index('user_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_kk_lottery');
    }
}