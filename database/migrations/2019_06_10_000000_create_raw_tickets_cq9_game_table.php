<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsCq9GameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_cq9_game');

        Schema::create('raw_tickets_cq9_game', function (Blueprint $table) {
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
            $table->string('gamehall')->nullable()
                ->comment('遊戲商名稱');

            $table->string('gametype')->nullable()
                ->comment('遊戲種類');

            $table->string('gameplat')->nullable()
                ->comment('遊戲平台');

            $table->string('gamecode')->nullable()
                ->comment('遊戲代碼');

            $table->string('account')->nullable()
                ->comment('玩家帳號');

            $table->string('round')->nullable()
                ->comment('遊戲局號');

            $table->decimal('balance', 10, 2)->nullable()
                ->comment('遊戲後餘額');

            $table->decimal('win', 10, 2)->nullable()
                ->comment('遊戲贏分（已包含彩池獎金及從PC贏得的金額）');

            $table->decimal('bet', 10, 2)->nullable()
                ->comment('下注金額');

            $table->decimal('jackpot', 10, 2)->nullable()
                ->comment('彩池獎金');

            $table->decimal('winpc', 10, 2)->nullable()
                ->comment('從PC贏得的金額,此欄位為牌桌遊戲使用');

            $table->text('jackpotcontribution')->nullable()
                ->comment('彩池獎金貢獻值,從小彩池到大彩池依序排序');

            $table->string('jackpottype')->nullable()
                ->comment('彩池獎金類別,此欄位值為空字串時，表示未獲得彩池獎金');

            $table->string('status')->nullable()
                ->comment('注單狀態,complete:完成');

            $table->string('endroundtime')->nullable()
                ->comment('遊戲結束時間，格式為 RFC3339');

            $table->string('createtime')->nullable()
                ->comment('當筆資料建立時間，格式為 RFC3339,系統結算時間');

            $table->string('bettime')->nullable()
                ->comment('下注時間,格式為 RFC3339');

            $table->text('detail')->nullable()
                ->comment('回傳 free game / bonus game / luckydraw / item / reward 資訊');

            $table->boolean('singlerowbet')->nullable()
                ->comment('是否為再旋轉形成的注單');

            $table->string('gamerole')->nullable()
                ->comment('庄(banker) or 閒(player),此欄位為牌桌遊戲使用，非牌桌遊戲此欄位值為空字串');

            $table->string('bankertype')->nullable()
                ->comment('對戰玩家是否有真人,此欄位為牌桌遊戲使用，非牌桌遊戲此欄位值為空字串');

            $table->decimal('rake', 10, 2)->nullable()
                ->comment('抽水金額,此欄位為牌桌遊戲使用');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
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
        Schema::dropIfExists('raw_tickets_cq9_game');
    }
}