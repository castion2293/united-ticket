<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsMayaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_maya', function (Blueprint $table) {
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
            $table->string('GameType')->nullable()
                ->comment('遊戲名稱');

            $table->string('Username')->nullable()
                ->comment('用戶名');

            $table->bigInteger('GameMemberID')->nullable()
                ->comment('遊戲平台會員主鍵ID');

            $table->string('BetNo')->nullable()
                ->comment('注單編號');

            $table->decimal('BetMoney', 12, 2)->nullable()
                ->comment('下注金額');

            $table->decimal('ValidBetMoney',12 , 2)->nullable()
                ->comment('有效下注金額');

            $table->decimal('WinLoseMoney', 12, 2)->nullable()
                ->comment('輸贏金額');

            $table->decimal('Handsel', 12, 2)->nullable()
                ->comment('打賞');

            $table->text('BetDetail')->nullable()
                ->comment('下注內容');

            $table->integer('State')->nullable()
                ->comment('注單狀態(2:正常結算,3:該局取消,4:已被改單)');

            $table->integer('BetType')->nullable()
                ->comment('類型(0:下注,1:打賞)');

            $table->string('BetDateTime')->nullable()
                ->comment('下注時間');

            $table->string('CountDateTime')->nullable()
                ->comment('結算時間');

            $table->string('AccountDateTime')->nullable()
                ->comment('帳務時間');

            $table->decimal('Odds', 12, 2)->nullable()
                ->comment('注單賠率');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['BetNo']);
            // 指定查詢索引
            $table->index('Username');
            $table->index('BetNo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_maya');
    }
}
