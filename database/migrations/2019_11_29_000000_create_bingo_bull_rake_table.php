<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBingoBullRakeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bingo_bull_rakes', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('id')
                ->comment('資料識別碼');

            $table->string('bet_num')
                ->comment('[UK]原生注單編號');

            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            // === 各遊戲站的注單欄位 ===
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

            $table->decimal('rake', 10, 2)->nullable()
                ->comment('抽水點數');

            // 投注時間
            $table->datetime('bet_at')
                ->comment('投注時間');

            // 派彩時間
            $table->datetime('payout_at')->nullable()->default(null)
                ->comment('派彩時間');

            for($i = 0 ; $i < 13; $i++ ) {
                $table->char("depth_{$i}_identify", 26)->default('')
                    ->comment('代理商識別');
                $table->decimal("depth_{$i}_rake")->default(0)
                    ->comment('退水占成比例');
            }

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['id']);

            // 指定查詢索引
            $table->index('bet_num');
            $table->index('user_identify');
            $table->index('username');

            $table->index([
                'bet_at',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_2_index');

            $table->index([
                'bet_at',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_3_index');

            $table->index([
                'bet_at',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_4_index');

            $table->index([
                'bet_at',
                'depth_5_identify',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_5_index');

            $table->index([
                'bet_at',
                'depth_6_identify',
                'depth_5_identify',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_6_index');

            $table->index([
                'bet_at',
                'depth_7_identify',
                'depth_6_identify',
                'depth_5_identify',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_7_index');

            $table->index([
                'bet_at',
                'depth_7_identify',
                'depth_6_identify',
                'depth_5_identify',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'user_identify',
                'game_scope',
            ], 'depth_8_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_bull_rakes');
    }
}