<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsSuperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_super', function (Blueprint $table) {
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
            $table->string('m_id')->nullable()
                ->comment('會員帳號');

            $table->string('m_name')->nullable()
                ->comment('會員暱稱');

            $table->string('up_no1')->nullable()
                ->comment('代理帳號');

            $table->string('up_no2')->nullable()
                ->comment('總代理帳號');

            $table->string('sn')->nullable()
                ->comment('注單單號');

            $table->text('matter')->nullable()
                ->comment('注單內容');

            $table->string('gameSN')->nullable()
                ->comment('賽事ID');

            $table->string('gsn')->nullable()
                ->comment('玩法ID');

            $table->dateTime('m_date')->nullable()
                ->comment('投注時間');

            $table->dateTime('count_date')->nullable()
                ->comment('結帳時間');

            $table->string('team_no')->nullable()
                ->comment('球類');

            $table->string('fashion')->nullable()
                ->comment('玩法');

            $table->string('g_type')->nullable()
                ->comment('類型');

            $table->string('league')->nullable()
                ->comment('聯盟名稱');

            $table->string('gold')->nullable()
                ->comment('投注金額');

            $table->string('bet_gold')->nullable()
                ->comment('有效投注金額');

            $table->string('sum_gold')->nullable()
                ->comment('獲利');

            $table->string('result_gold')->nullable()
                ->comment('輸贏結果');

            $table->string('main_team')->nullable()
                ->comment('主隊');

            $table->string('visit_team')->nullable()
                ->comment('客隊');

            $table->string('mv_set')->nullable()
                ->comment('下注隊伍');

            $table->string('mode')->nullable()
                ->comment('盤口位置');

            $table->string('chum_num')->nullable()
                ->comment('投注時的盤口');

            $table->string('compensate')->nullable()
                ->comment('投注時的賠率');

            $table->string('status')->nullable()
                ->comment('結帳狀態');

            $table->string('score1')->nullable()
                ->comment('主隊分數');

            $table->string('score2')->nullable()
                ->comment('客隊分數');

            $table->string('status_note')->nullable()
                ->comment('注單狀態');

            $table->string('end')->nullable()
                ->comment('結算');

            $table->string('updated_msg')->nullable()
                ->comment('派彩修正記錄');

            $table->string('now')->nullable()
                ->comment('當日 / 歷史投注');

            $table->text('detail')->nullable()
                ->comment('可能多筆，若有過關注單則顯示過關單資訊');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['sn']);
            // 指定查詢索引
            $table->index('m_id');
            $table->index('sn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_super');
    }
}
