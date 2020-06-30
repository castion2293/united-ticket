<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSaGamingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_sa_gaming', function (Blueprint $table) {
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
            $table->string('Username')
                ->comment('用戶名');

            $table->bigInteger('BetID')->nullable()
                ->comment('[UK] 投注記錄ID');

            $table->dateTime('BetTime')->nullable()
                ->comment('投注時間');

            $table->dateTime('PayoutTime')->nullable()
                ->comment('結算時間');

            $table->string('GameID')->nullable()
                ->comment('遊戲ID');

            $table->integer('HostID')->nullable()
                ->comment('桌台ID');

            $table->string('HostName')->nullable()
                ->comment('桌台名稱');

            $table->string('GameType')->nullable()
                ->comment('遊戲類型');

            $table->integer('Set')->nullable()
                ->comment('靴數');

            $table->integer('Round')->nullable()
                ->comment('局數');

            $table->integer('BetType')->nullable()
                ->comment('投注類型');

            $table->decimal('BetAmount', 10, 2)->nullable()
                ->comment('投注額');

            $table->decimal('Rolling', 10, 2)->nullable()
                ->comment('洗碼量');

            $table->string('Detail')->nullable()
                ->comment('電子遊藝/彩票');

            $table->text('GameResult')->nullable()
                ->comment('遊戲結果');

            $table->decimal('ResultAmount', 10, 2)->nullable()
                ->comment('輸贏金額');

            $table->decimal('Balance', 10, 2)->nullable()
                ->comment('餘額');

            $table->string('State')->nullable()
                ->comment('投注記錄');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['BetID']);
            // 指定查詢索引
            $table->index('Username');
            $table->index('BetID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_sa_gaming');
    }
}
