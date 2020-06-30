<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsAwcSexyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_awc_sexy');

        Schema::create('raw_tickets_awc_sexy', function (Blueprint $table) {
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
            $table->bigInteger('ID')->nullable()
                ->comment('注單編號');

            $table->char('userId', 20)->nullable()
                ->comment('會員帳號');

            $table->char('platformTxId', 30)->nullable()
                ->comment('平台 TxId');

            $table->char('platform', 20)->nullable()
                ->commnet('平台名稱');

            $table->char('gameCode', 20)->nullable()
                ->commnet('遊戲名稱');

            $table->char('gameType', 20)->nullable()
                ->comment('遊戲類別');

            $table->char('betType', 40)->nullable()
                ->comment('下注類型');

            $table->dateTime('txTime')->nullable()
                ->comment('交易時間');

            $table->decimal('betAmt', 14, 2)->nullable()
                ->comment('投注金額');

            $table->decimal('winAmt', 17, 2)->nullable()
                ->comment('中獎金額');

            $table->decimal('turnOver', 17, 2)->nullable()
                ->comment('');

            $table->integer('txStatus')->nullable()
                ->comment('交易狀態');

            $table->decimal('realBetAmt', 14, 2)->nullable()
                ->comment('有效投注金額');

            $table->decimal('realWinAmt', 17, 2)->nullable()
                ->comment('有效中獎金額');

            $table->decimal('jackpotBetAmt', 14, 2)->nullable()
                ->comment('');

            $table->decimal('jackpotWinAmt', 17, 2)->nullable()
                ->comment('');

            $table->char('currency', 5)->nullable()
                ->comment('幣別');

            $table->decimal('comm', 14, 2)->nullable()
                ->comment('佣金');

            $table->dateTime('createTime')->nullable()
                ->comment('建立日期');

            $table->dateTime('updateTime')->nullable()
                ->comment('更新日期');

            $table->dateTime('bizDate')->nullable()
                ->comment('帳務日期');

            $table->dateTime('modifyTime')->nullable()
                ->comment('修改日期');

            $table->char('roundId', 40)->nullable()
                ->comment('遊戲局號');

            $table->text('gameInfo')->nullable()
                ->comment('注單資訊');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('ID');
            $table->index('userId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_awc_sexy');
    }
}
