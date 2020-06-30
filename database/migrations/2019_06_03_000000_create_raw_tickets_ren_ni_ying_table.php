<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsRenNiYingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_ren_ni_ying');

        Schema::create('raw_tickets_ren_ni_ying', function (Blueprint $table) {
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
            $table->string('id')->nullable()
                ->comment('注單號');

            $table->integer('status')->nullable()
                ->comment('下注項狀態，1 = 已結算, 0 = 未結算, 2 = 取消單');

            $table->string('userId')->nullable()
                ->comment('下注會員登入 Id');

            $table->string('created')->nullable()
                ->comment('注單成立時間');

            $table->integer('gameId')->nullable()
                ->comment('遊戲 Id');

            $table->string('roundId')->nullable()
                ->comment('遊戲期號');

            $table->string('place')->nullable()
                ->comment('下注欄目');

            $table->string('guess')->nullable()
                ->comment('下注目標');

            $table->decimal('odds', 5, 2)->nullable()
                ->comment('賠率');

            $table->decimal('money', 10, 2)->nullable()
                ->comment('下注金額');

            $table->decimal('result', 10, 2)->nullable()
                ->comment('輸贏結果');

            $table->decimal('playerRebate', 10, 2)->nullable()
                ->comment('會員退水金額');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('id');
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
        Schema::dropIfExists('raw_tickets_ren_ni_ying');
    }
}