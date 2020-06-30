<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsWMCasinoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_wm_casino');

        Schema::create('raw_tickets_wm_casino', function (Blueprint $table) {
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
            $table->integer('betId')->nullable()
                ->comment('注单号');

            $table->string('user')->nullable()
                ->comment('账号');

            $table->date('betTime')->nullable()
                ->comment('下注時間');

            $table->float('beforeCash',10,4)->nullable()
                ->comment('下注前餘額');

            $table->float('bet',10,4)->nullable()
                ->comment('下注金額');

            $table->float('validbet',10,4)->nullable()
                ->comment('有效下注');

            $table->float('water',10,4)->nullable()
                ->comment('退水金額');

            $table->float('result',10,4)->nullable()
                ->comment('下注結果');

            $table->string('betResult')->nullable()
                ->comment('下注内容 ex:"Banker"');

            $table->string('waterbet')->nullable()
                ->comment('下注退水金额');

            $table->float('winLoss', 10,4)->nullable()
                ->comment('输赢金额');

            $table->string('ip')->nullable()
                ->comment('IP');

            $table->integer('gid')->nullable()
                ->comment('游戏类别编号 101:百家乐,102:龙虎,103:轮盘,104:骰宝,105:牛牛,106:三公,107:番摊,108:色碟,110:鱼虾蟹,111:炸金花');

            $table->integer('event')->nullable()
                ->comment('场次编号');

            $table->integer('round')->nullable()
                ->comment('场次编号');

            $table->integer('eventChild')->nullable()
                ->comment('子场次编号');

            $table->integer('subround')->nullable()
                ->comment('子场次编号');

            $table->integer('tableId')->nullable()
                ->comment('桌台编号');

            $table->string('gameResult')->nullable()
                ->comment('牌型 ex:庄:♦3♦3 闲:♥9♣10');

            $table->string('gname')->nullable()
                ->comment('游戏名称 ex:百家乐');

            $table->integer('commission')->nullable()
                ->comment('0:一般, 1:免佣');

            $table->string('reset')->nullable()
                ->comment('Y:有重对, N:非重对');

            $table->date('settime')->nullable()
                ->comment('结算时间');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('betId');
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
        Schema::dropIfExists('raw_tickets_wm_casino');
    }
}