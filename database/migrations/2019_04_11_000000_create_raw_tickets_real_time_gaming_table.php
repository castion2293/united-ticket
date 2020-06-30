<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsRealTimeGamingTable extends Migration
{
    protected $table = 'raw_tickets_real_time_gaming';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
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
            $table->string('agentId')->nullable()
                ->comment('agentId');

            $table->string('agentName')->nullable()
                ->comment('agentName');

            $table->string('casinoPlayerId')->nullable()
                ->comment('casinoPlayerId');

            $table->string('casinoId')->nullable()
                ->comment('casinoId');

            $table->string('playerName')->nullable()
                ->comment('playerName');

            $table->string('gameDate')->nullable()
                ->comment('gameDate');

            $table->string('gameStartDate')->nullable()
                ->comment('gameStartDate');

            $table->string('gameNumber')->nullable()
                ->comment('gameNumber');

            $table->string('gameName')->nullable()
                ->comment('gameName');

            $table->string('gameId')->nullable()
                ->comment('gameId');

            $table->string('bet')->nullable()
                ->comment('bet');

            $table->string('win')->nullable()
                ->comment('win');

            $table->string('jpBet')->nullable()
                ->comment('jpBet');

            $table->string('jpWin')->nullable()
                ->comment('jpWin');

            $table->string('currency')->nullable()
                ->comment('currency');

            $table->string('roundId')->nullable()
                ->comment('roundId');

            $table->string('balanceStart')->nullable()
                ->comment('balanceStart');

            $table->string('balanceEnd')->nullable()
                ->comment('balanceEnd');

            $table->string('platform')->nullable()
                ->comment('platform');

            $table->string('externalGameId')->nullable()
                ->comment('externalGameId');

            $table->string('sideBet')->nullable()
                ->comment('sideBet');

            $table->string('jpType')->nullable()
                ->comment('jpType');

            $table->string('jackpotBet')->nullable()
                ->comment('jackpotBet');

            $table->string('id')->nullable()
                ->comment('id');



            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // TODO: 指定聯合鍵 與 指定查詢索引 需依照不同遊戲商調整

            // 指定查詢索引
            $table->index('playerName');
            $table->index('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
}