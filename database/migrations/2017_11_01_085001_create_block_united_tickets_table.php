<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlockUnitedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('block_united_tickets', function (Blueprint $table) {

            // [PK] 資料識別碼
            $table->string('id')
                ->comment('資料識別碼');

            // [FK] 會員帳號識別碼
            $table->uuid('user_identify')->nullable()->default('')
                ->comment('會員帳號識別碼');

            // [FK] 會員帳號識別碼
            $table->integer('ticket_count')
                ->comment('在此區間被壓縮的原生注單數');

            $table->string('username')->nullable()->default('')
                ->comment('會員帳號');

            $table->string('station')->default('')
                ->comment('遊戲服務站');

            // 遊戲範疇，例如： 美棒、日棒
            $table->string('game_scope')->default('')
                ->comment('類型');

                // 產品類型，例如： battle、battle
                $table->string('category')->default('')
                    ->comment('產品類型');

            // 押注類型，例如： 模式+場次+玩法
            // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
            $table->string('bet_type')->nullable()->default(null)
                ->comment('玩法');

            // 實際投注一刻鐘內總計
            $table->decimal('sum_raw_bet', 9, 2)->unsigned()->default(0)
                ->comment('實際投注');

            // 一刻鐘內總計
            // 有效投注 (處理中洞、退組、合局情況之後的投注額)
            // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
            $table->decimal('sum_valid_bet', 9, 2)->unsigned()->default(0)
                ->comment('有效投注');

            // 洗碼量 (扣掉同局對押情況之後的投注額)
            // 若沒有特別情況，該欄位資料應該會和實際投注一樣
            $table->decimal('sum_rolling', 9, 2)->unsigned()->default(0)
                ->comment('洗碼量');

            // 輸贏結果(可正可負)
            $table->decimal('sum_winnings', 9, 2)->default(0)
                ->comment('輸贏結果');

            // 彩金
            $table->decimal('sum_bonus', 9, 2)->default(0)
                ->comment('彩金');

            // 資料開始時間
            $table->datetime('time_span_begin')
                ->comment('資料開始時間');

            // 資料結束時間
            $table->datetime('time_span_end')
                ->comment('資料結束時間');

            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            for($i = 0 ; $i < 13; $i++ ) {
                $table->string("depth_{$i}_identify")->default('')
                    ->comment('代理商識別');
                $table->decimal("depth_{$i}_ratio")->default(0)
                    ->comment('占成比例');
            }

            // === 索引 ===
            // 指定主鍵
            $table->primary(['id']);
            // 指定索引
            $table->index(['game_scope', 'bet_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('block_united_tickets');
    }
}
