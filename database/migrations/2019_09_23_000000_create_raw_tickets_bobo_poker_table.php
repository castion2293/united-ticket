<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsBoboPokerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_bobo_poker');

        Schema::create('raw_tickets_bobo_poker', function (Blueprint $table) {
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
            $table->char('account', 32)->nullable()
                ->comment('會員帳號');

            $table->integer('betId')->nullable()
                ->comment('注單編號');

            $table->char('gameNumber', 32)->nullable()
                ->comment('遊戲局號');

            $table->char('gameName', 32)->nullable()
                ->comment('遊戲名稱');

            $table->text('result')->nullable()
                ->comment('開牌結果');

            $table->integer('betDetailId')->nullable()
                ->comment('詳細注單編號');

            $table->integer('betAmt')->nullable()
                ->comment('下注金額');

            $table->integer('earn')->nullable()
                ->comment('該注盈虧');

            $table->string('content')->nullable()
                ->comment('該注內容');

            $table->char('betTime', 32)->nullable()
                ->comment('下注時間');

            $table->char('payoutTime', 32)->nullable()
                ->comment('該注結算時間');

            $table->char('status', 2)->nullable()
                ->comment('該注狀態,0未結算,1已結算,X注銷單');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('account');
            $table->index('betId');
            $table->index('betDetailId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_bobo_poker');
    }
}