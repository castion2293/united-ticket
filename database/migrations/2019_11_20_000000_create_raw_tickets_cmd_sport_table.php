<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsCmdSportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_cmd_sport');

        Schema::create('raw_tickets_cmd_sport', function (Blueprint $table) {
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
            $table->integer('Id')->nullable()
                ->comment('供查询的自增ID');

            $table->char('SourceName', 30)->nullable()
                ->comment('会员在合作商平台的标识');

            $table->char('ReferenceNo', 50)->nullable()
                ->comment('单号');

            $table->integer('SocTransId')->nullable()
                ->comment('数据ID');

            $table->boolean('IsFirstHalf')->nullable()
                ->comment('是否是上半场');

            $table->datetime('TransDate')->nullable()
                ->comment('下单时间');

            $table->boolean('IsHomeGive')->nullable()
                ->comment('是否为主队让球');

            $table->boolean('IsBetHome')->nullable()
                ->comment('是否投注主队');

            $table->decimal('BetAmount', 20, 2)->nullable()
                ->comment('下注金额');

            $table->decimal('Outstanding', 20, 4)->nullable()
                ->comment('用户未结算余额');

            $table->decimal('Hdp', 20, 2)->nullable()
                ->comment('让球数');

            $table->decimal('Odds', 18, 2)->nullable()
                ->comment('投注时的赔率');

            $table->char('Currency', 10)->nullable()
                ->comment('货币代码');

            $table->decimal('WinAmount', 20, 2)->nullable()
                ->comment('输赢金额');

            $table->decimal('ExchangeRate', 20, 2)->nullable()
                ->comment('会员货币转换为马币的汇率');

            $table->char('WinLoseStatus', 10)->nullable()
                ->comment('输赢状态');

            $table->char('TransType', 10)->nullable()
                ->comment('玩法类型');

            $table->char('DangerStatus', 5)->nullable()
                ->comment('注單類型');

            $table->decimal('MemCommissionSet', 20, 4)->nullable()
                ->comment('佣金设定值');

            $table->decimal('MemCommission', 20, 4)->nullable()
                ->comment('会员所得佣金');

            $table->char('BetIp', 50)->nullable()
                ->comment('下注时的IP');

            $table->integer('HomeScore')->nullable()
                ->comment('主队最终得分');

            $table->integer('AwayScore')->nullable()
                ->comment('客队最终得分');

            $table->integer('RunHomeScore')->nullable()
                ->comment('投注时主队得分');

            $table->integer('RunAwayScore')->nullable()
                ->comment('投注时客队得分');

            $table->boolean('IsRunning')->nullable()
                ->comment('是否为滚球');

            $table->string('RejectReason')->nullable()
                ->comment('拒绝理由');

            $table->char('SportType', 10)->nullable()
                ->comment('球类标识');

            $table->integer('Choice')->nullable()
                ->comment('投注位置');

            $table->datetime('WorkingDate')->nullable()
                ->comment('所属做账日期');

            $table->char('OddsType', 5)->nullable()
                ->comment('盤口類型');

            $table->datetime('MatchDate')->nullable()
                ->comment('球赛开赛日期');

            $table->integer('HomeTeamId')->nullable()
                ->comment('主队球队ID');

            $table->integer('AwayTeamId')->nullable()
                ->comment('客队球队ID');

            $table->integer('LeagueId')->nullable()
                ->comment('联赛ID');

            $table->char('SpecialId', 10)->nullable()
                ->comment('特别投注名称ID');

            $table->integer('StatusChange')->nullable()
                ->comment('是否重算标识(>=2 代表有重算)');

            $table->datetime('StateUpdateTs')->nullable()
                ->comment('注单结算时间');

            $table->boolean('IsCashOut')->nullable()
                ->comment('会员是否已经卖单');

            $table->decimal('CashOutTotal', 20, 4)->nullable()
                ->comment('如果会员已经卖单,则为总卖单金额');

            $table->decimal('CashOutTakeBack', 20, 4)->nullable()
                ->comment('如果会员已经卖单,则为卖单所得金额');

            $table->decimal('CashOutWinLoseAmount', 20, 4)->nullable()
                ->comment('如果会员已经卖单,则为会员卖单输赢');

            $table->integer('BetSource')->nullable()
                ->comment('下注平台');

            $table->string('AOSExcluding')->nullable()
                ->comment('如果下注AOS则为不包括在AOS内的比分');

            $table->decimal('MMRPercent', 20, 4)->nullable()
                ->comment('MMK币别专用，其他币别显示0.0000');

            $table->integer('MatchID')->nullable()
                ->comment('比赛ID');

            $table->char('MatchGroupID', 50)->nullable()
                ->comment('比赛唯一标识符');

            $table->string('BetRemarks')->nullable()
                ->comment('OddsTrader系统专用值. 若非OddsTrader传送来的值, 即回传String.Empty');

            $table->boolean('IsSpecial')->nullable()
                ->comment('是否是特別投注');



            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('Id');
            $table->index('SourceName');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_cmd_sport');
    }
}