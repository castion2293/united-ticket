<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('united_tickets', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('id')
                ->comment('資料識別碼');

            $table->string('bet_num')
                ->comment('[UK]原生注單編號');

            // [FK] 會員帳號識別碼
            $table->uuid('user_identify')->nullable()->default('')
                ->comment('會員帳號識別碼');

            $table->string('username')->default('')
                ->comment('會員帳號');

            // 遊戲服務站索引，例如： sa_gaming，必需符合 config/united-ticket.php 中的設定索引字串
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

            // 實際投注
            $table->decimal('raw_bet', 9, 2)->default(0)
                ->comment('實際投注');

            // 有效投注 (處理中洞、退組、合局情況之後的投注額)
            // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
            $table->decimal('valid_bet', 9, 2)->default(0)
                ->comment('有效投注');

            // 洗碼量 (扣掉同局對押情況之後的投注額)
            // 若沒有特別情況，該欄位資料應該會和實際投注一樣
            $table->decimal('rolling', 9, 2)->default(0)
                ->comment('洗碼量');

            // 輸贏結果(可正可負)
            $table->decimal('winnings', 9, 2)->default(0)
                ->comment('輸贏結果');

            // 彩金
            $table->decimal('bonus', 9, 2)->default(0)
                ->comment('彩金');

            // 開牌結果
            $table->text('game_result')
                ->comment('開牌結果');

            // 開彩結果
            $table->boolean('invalid')->default(false)
                ->comment('作廢 true:作廢，false:正常');

            // 投注時間
            $table->datetime('bet_at')
                ->comment('投注時間');

            // 派彩時間
            $table->datetime('payout_at')->nullable()->default(null)
                ->comment('派彩時間');

            // 分配時間
            $table->datetime('dispatch_done_at')->nullable()->default(null)
                ->comment('分配時間');

            // 分配對象
            $table->text('dispatch_target')->nullable()->default(null)
                ->comment('分配對象');

            // 分配後溢出來的洗碼量
            $table->decimal('dispatch_over_points', 9, 2)->default(0)
                ->comment('分配後溢出來的洗碼量');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
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
        Schema::dropIfExists('united_tickets');
    }
}
