<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsVSLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_vs_lottery');

        Schema::create('raw_tickets_vs_lottery', function (Blueprint $table) {
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
            $table->integer('FetchId')->nullable()
                ->comment('行數');

            $table->integer('TrID')->nullable()
                ->comment('交易ID');

            $table->integer('TrDetailID')->nullable()
                ->comment('細單ID');

            $table->datetime('TrDate')->nullable()
                ->comment('投注日期时间');

            $table->datetime('DrawDate')->nullable()
                ->comment('抽签日期');

            $table->char('UserName')->nullable()
                ->comment('会员用户名');

            $table->char('MarketName')->nullable()
                ->comment('市場名称');

            $table->char('BetType')->nullable()
                ->comment('投注类型名称');

            $table->char('BetNo')->nullable()
                ->comment('投注号码');

            $table->decimal('Turnover', 8, 3)->nullable()
                ->comment('總額');

            $table->decimal('CommAmt', 8, 3)->nullable()
                ->comment('佣金金额');

            $table->decimal('NetAmt', 8, 3)->nullable()
                ->comment('净投注额（营业额 - 佣金金额');

            $table->decimal('WinAmt', 8, 3)->nullable()
                ->comment('赢/输金额');

            $table->decimal('Stake', 8, 3)->nullable()
                ->comment('赌注');

            $table->integer('StrikeCount')->nullable()
                ->comment('與中獎號碼一樣的次數');

            $table->decimal('Odds1', 8, 3)->nullable()
                ->comment('赔率1');

            $table->decimal('Odds2', 8, 3)->nullable()
                ->comment('赔率2');

            $table->decimal('Odds3', 8, 3)->nullable()
                ->comment('赔率3');

            $table->decimal('Odds4', 8, 3)->nullable()
                ->comment('赔率4');

            $table->decimal('Odds5', 8, 3)->nullable()
                ->comment('赔率5');

            $table->char('CurCode')->nullable()
                ->comment('幣別');

            $table->char('WinLossStatus')->nullable()
                ->comment('注單狀態');

            $table->char('IsPending')->nullable()
                ->comment('處理狀態');

            $table->char('IsCancelled')->nullable()
                ->comment('取消狀態');

            $table->datetime('LastChangeDate')->nullable()
                ->comment('上次更新日期/时间');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('TrDetailID');
            $table->index('UserName');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_vs_lottery');
    }
}