<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsDreamGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_dream_game', function (Blueprint $table) {
            // === 統一必要的欄位 ===
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
            $table->bigInteger('id')->nullable()
                ->comment('注单唯一Id');

            $table->integer('lobbyId')->nullable()
                ->comment('游戏大厅号 1:旗舰厅, 2:竞咪厅, 3:现场厅, 4:波贝厅');

            $table->integer('tableId')->nullable()
                ->comment('游戏桌号');

            $table->bigInteger('shoeId')->nullable()
                ->comment('游戏靴号');

            $table->bigInteger('playId')->nullable()
                ->comment('游戏局号');

            $table->integer('gameType')->nullable()
                ->comment('游戏类型');

            $table->integer('gameId')->nullable()
                ->comment('游戏Id');

            $table->bigInteger('memberId')->nullable()
                ->comment('会员Id');

            $table->integer('parentId')->nullable()
                ->comment('parentId');

            $table->dateTime('betTime')->nullable()
                ->comment('游戏下注时间');

            $table->dateTime('calTime')->nullable()
                ->comment('游戏结算时间');

            $table->double('winOrLoss')->nullable()
                ->comment('派彩金额 (输赢应扣除下注金额)');

            $table->double('winOrLossz')->nullable()
                ->comment('好路追注派彩金额');

            $table->double('balanceBefore')->nullable()
                ->comment('balanceBefore');

            $table->double('betPoints')->nullable()
                ->comment('下注金额');

            $table->double('betPointsz')->nullable()
                ->comment('好路追注金额');

            $table->double('availableBet')->nullable()
                ->comment('有效下注金额');

            $table->string('userName')->nullable()
                ->comment('会员登入账号');

            $table->text('result')->nullable()
                ->comment('游戏结果');

            $table->text('betDetail')->nullable()
                ->comment('下注注单');

            $table->text('betDetailz')->nullable()
                ->comment('好路追注注单');

            $table->string('ip')->nullable()
                ->comment('下注时客户端IP	');

            $table->string('ext')->nullable()
                ->comment('游戏唯一ID');

            $table->integer('isRevocation')->nullable()
                ->comment('是否结算：0:未结算, 1:已结算, 2:已撤销(该注单为对冲注单)');

            $table->bigInteger('parentBetId')->nullable()
                ->comment('撤销的那比注单的ID');

            $table->integer('currencyId')->nullable()
                ->comment('货币ID');

            $table->integer('deviceType')->nullable()
                ->comment('下注时客户端类型');

            $table->integer('roadid')->nullable()
                ->comment('roadid');

            $table->bigInteger('pluginid')->nullable()
                ->comment('追注转账流水号');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['id']);
            // 指定查詢索引
            $table->index('userName');
            $table->index('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_dream_game');
    }
}
