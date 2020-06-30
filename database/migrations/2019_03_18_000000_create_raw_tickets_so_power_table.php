<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSoPowerTable extends Migration
{
    protected $table = 'raw_tickets_so_power';

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
            $table->string('USERNAME')->nullable()
                ->comment('會員帳號');

            $table->string('GTYPE')->nullable()
                ->comment('遊戲代碼');

            $table->string('BETID')->nullable()
                ->comment('注單號碼');

            $table->string('RTYPE')->nullable()
                ->comment('玩法');

            $table->string('GOLD')->nullable()
                ->comment('下注金額');

            $table->string('IORATIO')->nullable()
                ->comment('賠率');

            $table->string('RESULT')->nullable()
                ->comment('輸贏結果');

            $table->string('ADDDATE')->nullable()
                ->comment('下注時間');

            $table->string('WINGOLD')->nullable()
                ->comment('輸贏金額');

            $table->string('WGOLD_DM')->nullable()
                ->comment('退水金額');

            $table->string('ORDERIP')->nullable()
                ->comment('下注 IP');

            $table->string('BETCONTENT')->nullable()
                ->comment('下注內容');

            $table->string('PERIODNUMBER')->nullable()
                ->comment('下注期號');

            $table->string('BETDETAIL')->nullable()
                ->comment('下注結果(為下注結果輔助說明，實際值應以各欄位為主)');

            $table->string('RESULT_OK')->nullable()
                ->comment('是否已結算');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // TODO: 指定聯合鍵 與 指定查詢索引 需依照不同遊戲商調整

            // 指定查詢索引
            $table->index('USERNAME');
            $table->index('BETID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
}