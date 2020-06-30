<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsUfaSportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_ufa_sport');

        Schema::create('raw_tickets_ufa_sport', function (Blueprint $table) {
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
            $table->integer('fid')->nullable()
                ->comment('取得注單id');

            $table->string('id')->nullable()
                ->comment('注單id');

            $table->string('t')->nullable()
                ->comment('修改日期，最長為毫秒');

            $table->string('u')->nullable()
                ->comment('玩家帳號');

            $table->integer('b')->nullable()
                ->comment('下注金額');

            $table->integer('w')->nullable()
                ->comment('贏得金額');

            $table->integer('a')->nullable()
                ->comment('佣金金額');

            $table->decimal('c', 10, 2)->nullable()
                ->comment('佣金百分比');

            $table->string('ip')->nullable()
                ->comment('IP位址');

            $table->integer('league')->nullable()
                ->comment('聯盟ID');

            $table->integer('home')->nullable()
                ->comment('主隊ID');

            $table->integer('away')->nullable()
                ->comment('客隊ID');

            $table->string('status')->nullable()
                ->comment('狀態(N：自动接受，A：接受，R：拒绝，C：取消// RG：拒绝目标，RP：拒绝罚款，RR：拒绝红牌)');

            $table->string('game')->nullable()
                ->comment('下注種類(HDP,1X2,OU,OE,CS,TG,FLG,HFT,PAR,ORT)');

            $table->decimal('odds', 10, 2)->nullable()
                ->comment('賠率');

            $table->text('side')->nullable()
                ->comment('下注結果(1.主隊, 2:客隊, X:平手)');

            $table->string('info')->nullable()
                ->comment('資訊(讓分、正確得分、總進球、上/下半場)');

            $table->integer('half')->nullable()
                ->comment('注單玩法(0. 全場  1.上半場)');

            $table->datetime('trandate')->nullable()
                ->comment('下注日期');

            $table->datetime('workdate')->nullable()
                ->comment('結算日期');

            $table->string('matchdate')->nullable()
                ->comment('比賽時間');

            $table->text('runscore')->nullable()
                ->comment('得分 (沒得分則空的)');

            $table->text('score')->nullable()
                ->comment('分數 (沒分數則空的)');

            $table->text('htscore')->nullable()
                ->comment('半場得分 (沒有則空的)');

            $table->text('flg')->nullable()
                ->comment('第一個 / 最後一個結果  (沒有則空的)');

            $table->string('res')->nullable()
                ->comment('開牌結果(P：不匹配  WA：贏得所有 LA：失去所有 WH：贏半  LH：失去一半  D：平局)');

            $table->integer('sportstype')->nullable()
                ->comment('遊戲種類id');

            $table->string('oddstype')->nullable()
                ->comment('盤口');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('u');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_ufa_sport');
    }
}