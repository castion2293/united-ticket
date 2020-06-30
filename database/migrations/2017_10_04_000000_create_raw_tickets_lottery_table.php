<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_lottery', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            $table->string('_0_account')
                ->comment('[UK] 會員帳號');

            $table->integer('_1_game_id')
                ->comment('[UK] 遊戲編號 11:六合 12:大樂 13:539');

            $table->date('_2_bet_date')
                ->comment('[UK] 報表時間');

            $table->integer('_3_bet_count')
                ->comment('下注筆數');

            $table->decimal('_4_bet_amount', 10, 2)
                ->comment('下注金額');

            $table->decimal('_5_member_rake', 10, 2)
                ->comment('退水');

            $table->decimal('_6_member_result', 10, 2)
                ->comment('輸贏結果');

            $table->decimal('_7_transfer_amount', 10, 2)
                ->comment('移轉金額');

            $table->decimal('_8_transfer_rake', 10, 2)
                ->comment('移轉退水');

            $table->decimal('_9_transfer_result', 10, 2)
                ->comment('移轉中獎金額');

            $table->decimal('_10_system_percent', 10, 2)
                ->comment('系統商佔成');

            $table->decimal('_11_system_rake', 10, 2)
                ->comment('系統商退水');

            $table->decimal('_12_system_result', 10, 2)
                ->comment('系統商中獎金額');

            $table->decimal('_13_company_percent', 10, 2)
                ->comment('公司佔成');

            $table->decimal('_14_company_rake', 10, 2)
                ->comment('公司退水');

            $table->decimal('_15_company_result', 10, 2)
                ->comment('公司中獎金額');

            $table->decimal('_16_moderator_percent', 10, 2)
                ->comment('版主佔成');

            $table->decimal('_17_moderator_rake', 10, 2)
                ->comment('版主退水');

            $table->decimal('_18_moderator_result', 10, 2)
                ->comment('版主中獎金額');

            $table->decimal('_19_up_no6_percent', 10, 2)
                ->comment('大總監佔成');

            $table->decimal('_20_up_no6_rake', 10, 2)
                ->comment('大總監退水');

            $table->decimal('_21_up_no6_result', 10, 2)
                ->comment('大總監中獎金額');

            $table->decimal('_22_up_no5_percent', 10, 2)
                ->comment('總監佔成');

            $table->decimal('_23_up_no5_rake', 10, 2)
                ->comment('總監退水');

            $table->decimal('_24_up_no5_result', 10, 2)
                ->comment('總監中獎金額');

            $table->decimal('_25_up_no4_percent', 10, 2)
                ->comment('大股東佔成');

            $table->decimal('_26_up_no4_rake', 10, 2)
                ->comment('大股東退水');

            $table->decimal('_27_up_no4_result', 10, 2)
                ->comment('大股東中獎金額');

            $table->decimal('_28_up_no3_percent', 10, 2)
                ->comment('股東佔成');

            $table->decimal('_29_up_no3_rake', 10, 2)
                ->comment('股東退水');

            $table->decimal('_30_up_no3_result', 10, 2)
                ->comment('股東中獎金額');

            $table->decimal('_31_up_no2_percent', 10, 2)
                ->comment('總代佔成');

            $table->decimal('_32_up_no2_rake', 10, 2)
                ->comment('總代退水');

            $table->decimal('_33_up_no2_result', 10, 2)
                ->comment('總代中獎金額');

            $table->decimal('_34_up_no1_percent', 10, 2)
                ->comment('代理佔成');

            $table->decimal('_35_up_no1_rake', 10, 2)
                ->comment('代理退水');

            $table->decimal('_36_up_no1_result', 10, 2)
                ->comment('代理中獎金額');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['_0_account', '_1_game_id', '_2_bet_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_lottery');
    }
}
