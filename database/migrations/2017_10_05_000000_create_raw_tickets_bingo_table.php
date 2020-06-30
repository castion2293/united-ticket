<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsBingoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_bingo', function (Blueprint $table) {
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
            $table->string('account')->nullable()
                ->comment('帳號');

            $table->string('serial_no')->nullable()
                ->comment('注單序號');

            $table->string('bingo_no')->nullable()
                ->comment('賓果期號');

            $table->string('bet_suit')->nullable()
                ->comment('玩法');

            $table->string('bet_type_group')->nullable()
                ->comment('押注類型');

            $table->string('numbers')->nullable()
                ->comment('押注號碼(多個以逗號分開，通常只用在類型為 `星號` 與 `單猜` 時，此欄位才會有資料)');

            $table->string('bet')->nullable()
                ->comment('押注點數');

            $table->string('odds')->nullable()
                ->comment('賠率');

            $table->string('real_bet')->nullable()
                ->comment('有效投注');

            $table->string('real_rebate')->nullable()
                ->comment('有效退水');

            $table->string('bingo_type')->nullable()
                ->comment('中獎押注類型');

            $table->string('bingo_odds')->nullable()
                ->comment('中獎賠率');

            $table->string('result')->nullable()
                ->comment('核獎結果');

            $table->string('status')->nullable()
                ->comment('狀態');

            $table->string('win_lose')->nullable()
                ->comment('輸贏獎金');

            $table->string('remark')->nullable()
                ->comment('備註');

            $table->string('bet_at')->nullable()
                ->comment('建立時間');

            $table->string('adjust_at')->nullable()
                ->comment('更新時間');

            $table->string('root_serial_no')->nullable()
                ->comment('改單目標單號');

            $table->string('root_created_at')->nullable()
                ->comment('改單時間');

            $table->boolean('duplicated')->nullable()
                ->comment('是否曾經改單 1改單 ,0未改單');

            $table->text('player')->nullable()
                ->comment('帳號資訊');

            $table->text('results')->nullable()
                ->comment('投注明細');

            $table->text('history')->nullable()
                ->comment('開獎結果');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['serial_no']);
            // 指定查詢索引
            $table->index('account');
            $table->index('serial_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_bingo');
    }
}
