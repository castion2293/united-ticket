<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsCockFightTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_cock_fight');

        Schema::create('raw_tickets_cock_fight', function (Blueprint $table) {
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
            $table->integer('ticket_id')->nullable()
                ->comment('注单号码');

            $table->char('login_id', 30)->nullable()
                ->comment('登录帐号');

            $table->char('arena_code', 10)->nullable()
                ->comment('赛场编号');

            $table->char('arena_name_cn', 10)->nullable()
                ->comment('赛场名中文名字');

            $table->char('match_no', 10)->nullable()
                ->comment('赛事编号');

            $table->char('match_type', 20)->nullable()
                ->comment('赛事类型');

            $table->datetime('match_date')->nullable()
                ->comment('赛事日期');

            $table->integer('fight_no')->nullable()
                ->comment('日场次');

            $table->datetime('fight_datetime')->nullable()
                ->comment('赛事时间');

            $table->char('meron_cock', 40)->nullable()
                ->comment('龍斗鸡');

            $table->char('meron_cock_cn', 10)->nullable()
                ->comment('龍斗鸡中文名字');

            $table->char('wala_cock', 40)->nullable()
                ->comment('鳳斗鸡');

            $table->char('wala_cock_cn', 10)->nullable()
                ->comment('鳳斗鸡中文名字');

            $table->char('bet_on', 10)->nullable()
                ->comment('投注 MERON:龍 WALA:鳳 BDD:和 FTD:大和');

            $table->char('odds_type', 10)->nullable()
                ->comment('赔率类型');

            $table->decimal('odds_asked', 8, 3)->nullable()
                ->comment('要求赔率');

            $table->decimal('odds_given', 8, 3)->nullable()
                ->comment('给出赔率');

            $table->integer('stake')->nullable()
                ->comment('投注金额');

            $table->decimal('stake_money', 18, 4)->nullable()
                ->comment('奖金');

            $table->decimal('balance_open', 18, 4)->nullable()
                ->comment('转账前余额');

            $table->decimal('balance_close', 18, 4)->nullable()
                ->comment('转账后余额');

            $table->datetime('created_datetime')->nullable()
                ->comment('创建时间');

            $table->char('fight_result', 10)->nullable()
                ->comment('投注 MERON:龍 WALA:鳳 BDD:和 FTD:大和');

            $table->char('status', 10)->nullable()
                ->comment('状态 WIN/LOSE/REFUND/CANCEL /VOID');

            $table->decimal('winloss', 18, 4)->nullable()
                ->comment('输赢');

            $table->decimal('comm_earned', 18, 4)->nullable()
                ->comment('所得佣金');

            $table->decimal('payout', 18, 4)->nullable()
                ->comment('派彩');

            $table->decimal('balance_open1', 18, 4)->nullable()
                ->comment('转账前余额');

            $table->decimal('balance_close1', 18, 4)->nullable()
                ->comment('转账后余额');

            $table->datetime('processed_datetime')->nullable()
                ->comment('处理时间');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('login_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_cock_fight');
    }
}