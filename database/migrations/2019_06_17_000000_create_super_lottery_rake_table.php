<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuperLotteryRakeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('super_lottery_rakes', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('id')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            // === 各遊戲站的注單欄位 ===
            $table->string('level')->nullable()
                ->comment('會員級別');

            $table->uuid('user_identify')->nullable()->default('')
                ->comment('會員帳號識別碼');

            $table->string('account')->nullable()
                ->comment('帳號');

            $table->dateTime('bet_date')->nullable()
                ->comment('日期');

            $table->string('game_scope')->nullable()
                ->comment('類型');

            $table->string('category')->default('')
                ->comment('產品類型');

            $table->integer('ccount')->nullable()
                ->comment('投注數量');

            $table->decimal('cmount', 13, 2)->nullable()
                ->comment('投注金額');

            $table->decimal('bmount', 13, 2)->nullable()
                ->comment('有效投注');

            $table->decimal('m_gold', 13, 2)->nullable()
                ->comment('會員中獎金額');

            $table->decimal('m_rake', 13, 4)->nullable()
                ->comment('會員退水');

            $table->decimal('m_result', 13, 2)->nullable()
                ->comment('結果會員');

            $table->decimal('up_no1_result', 13, 2)->nullable()
                ->comment('結果代理');

            $table->decimal('up_no2_result', 13, 2)->nullable()
                ->comment('結果總代理');

            $table->decimal('up_no1_rake', 13, 4)->nullable()
                ->comment('退水代理');

            $table->decimal('up_no2_rake', 13, 4)->nullable()
                ->comment('退水總代理');

            $table->string('up_no1')->nullable()
                ->comment('代理');

            $table->string('up_no2')->nullable()
                ->comment('總代理');

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
            $table->index('user_identify');
            $table->index('account');

            $table->index([
                'bet_date',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_2_index');

            $table->index([
                'bet_date',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_3_index');

            $table->index([
                'bet_date',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_4_index');

            $table->index([
                'bet_date',
                'depth_5_identify',
                'depth_4_identify',
                'depth_3_identify',
                'depth_2_identify',
                'depth_1_identify',
                'depth_0_identify',
                'game_scope',
            ], 'depth_5_index');

            $table->index([
                'bet_date',
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
                'bet_date',
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
                'bet_date',
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
        Schema::dropIfExists('super_lottery_rakes');
    }
}
