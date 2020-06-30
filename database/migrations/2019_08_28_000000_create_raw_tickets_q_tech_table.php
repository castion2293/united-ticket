<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsQTechTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_q_tech');

        Schema::create('raw_tickets_q_tech', function (Blueprint $table) {
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
                ->comment('游戏交易 id');

            $table->string('status')->nullable()
                ->comment('游戏局的状态，如 PENDING 或 COMPLETED 或 FAILED');

            $table->string('totalBet')->nullable()
                ->comment('该游戏局的总投注');

            $table->string('totalPayout')->nullable()
                ->comment('该游戏局的总派彩');

            $table->string('totalBonusBet')->nullable()
                ->comment('总奖金投注金额');

            $table->char('currency', 4)->nullable()
                ->comment('货币代码');

            $table->char('initiated', 60)->nullable()
                ->comment('游戏局创建的日期和时间');

            $table->char('completed', 60)->nullable()
                ->comment('游戏局完成的日期和时间');

            $table->char('playerId', 20)->nullable()
                ->comment('运营商系 统中玩家账号');

            $table->char('operatorId', 20)->nullable()
                ->comment('运营商在 QT 平台中的唯一标识符');

            $table->char('device', 20)->nullable()
                ->comment('该玩家的设 备，例如 MOBILE or DESKTOP');

            $table->string('gameProvider')->nullable()
                ->comment('游戏提供者的标识符');

            $table->string('gameId')->nullable()
                ->comment('游戏的标示符');

            $table->string('gameCategory')->nullable()
                ->comment('游戏类');

            $table->char('gameClientType', 20)->nullable()
                ->comment('游戏客户 端平台，比如 Flash 或 HTML5');

            $table->char('bonusType', 20)->nullable()
                ->comment('奖金类型，如 FREE_ROUND 或 FEATURE_TRIGGER');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // 指定查詢索引
            $table->index('id');
            $table->index('playerId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_q_tech');
    }
}