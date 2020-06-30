<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsIncorrectScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_incorrect_score');

        Schema::create('raw_tickets_incorrect_score', function (Blueprint $table) {
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
            $table->integer('ticketNo')->nullable()
                ->comment('注单号(唯一值)');

            $table->char('user', 30)->nullable()
                ->comment('会员账号');

            $table->integer('sportType')->nullable()
                ->comment('球类编号');

            $table->char('orderTime', 30)->nullable()
                ->comment('注单最后更新时间');

            $table->char('betTime', 20)->nullable()
                ->comment('下注时间');

            $table->decimal('betamount', 18, 2)->nullable()
                ->comment('下注金额');

            $table->decimal('validBetAmount', 18, 2)->nullable()
                ->comment('有效投注(计算返水用)');

            $table->char('currency', 30)->nullable()
                ->comment('币别');

            $table->decimal('winlose', 18, 2)->nullable()
                ->comment('输赢(未含本金)');

            $table->integer('isFinished')->nullable()
                ->comment('1:完场派彩 0:未完场');

            $table->char('statusType', 30)->nullable()
                ->comment('注单状态 Y:成功注单 V:取消注单');

            $table->char('betIp', 30)->nullable()
                ->comment('IP地址');

            $table->char('cType', 20)->nullable()
                ->comment('P:等待状态,WA:全赢,LA:全输,WH:赢半,LH:输半,D:平');

            $table->char('device', 10)->nullable()
                ->comment('P:计算机版,M:手机版');

            $table->char('accdate', 30)->nullable()
                ->comment('');

            $table->char('acctId', 50)->nullable()
                ->comment('');

            $table->char('detail', 255)->nullable()
                ->comment('詳細資訊');

            $table->integer('wagerGrpId')->nullable()
                ->comment('游戏类型编号');

            $table->integer('refNo')->nullable()
                ->comment('子单号');

            $table->char('league', 50)->nullable()
                ->comment('球队联盟');

            $table->char('match', 50)->nullable()
                ->comment('比赛队伍');

            $table->integer('betOption')->nullable()
                ->comment('1:主 2:客 3:和 4:大 5:小');

            $table->integer('hdp')->nullable()
                ->comment('让球方 0:主 1:客');

            $table->decimal('odds', 8, 3)->nullable()
                ->comment('赔率(欧赔含本金)');

            $table->char('winlostTime', 30)->nullable()
                ->comment('开奖时间');

            $table->string('ftScore', 20)->nullable()
                ->comment('比赛结果');

            $table->string('curScore', 20)->nullable()
                ->comment('当时比分');

            $table->string('wagerTypeID', 50)->nullable()
                ->comment('玩法编号');

            $table->string('cutline', 30)->nullable()
                ->comment('投注盘口');

            $table->string('odddesc', 30)->nullable()
                ->comment('赔率类型说明');

            $table->string('ScheduleTime', 30)->nullable()
                ->comment('开赛时间');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('ticketNo');
            $table->index('user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_incorrect_score');
    }
}