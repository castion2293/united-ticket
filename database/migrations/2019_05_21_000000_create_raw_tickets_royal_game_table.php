<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsRoyalGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('raw_tickets_royal_game');

        Schema::create('raw_tickets_royal_game', function (Blueprint $table) {
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
            $table->string('ID')->nullable()
                ->comment('注單流水號');

            $table->string('BucketID')->nullable()
                ->comment('分桶ID長度位於10之間的英數字');

            $table->string('BatchRequestID')->nullable()
                ->comment('批次下注請求編號，若為null表示為單次下注');

            $table->string('BetRequestID')->nullable()
                ->comment('下注請求編號');

            $table->string('BetScore')->nullable()
                ->comment('下注金額(虛貨量)');

            $table->string('BetStatus')->nullable()
                ->comment('注單狀態');

            $table->string('BetTime')->nullable()
                ->comment('下注時間');

            $table->string('BucketPublicScore')->nullable()
                ->comment('分桶公點(目前無用途)');

            $table->string('BucketRebateRate')->nullable()
                ->comment('分桶退水比例(目前無用途)');

            $table->string('ClientIP')->nullable()
                ->comment('用戶端IP');

            $table->string('ClientType')->nullable()
                ->comment('客戶端類型ID (1:網頁版,2:下載版,3:手機版)');

            $table->string('Currency')->nullable()
                ->comment('幣別');

            $table->integer('CurrentScore')->nullable()
                ->comment('當下下注後餘額');

            $table->string('DetailURL')->nullable()
                ->comment('顯示遊戲系統下注補充資料，如免費遊戲、開牌結果');

            $table->string('ExchangeRate')->nullable()
                ->comment('匯率');

            $table->string('FinalScore')->nullable()
                ->comment('總輸贏，輸贏結果(正負值) + 玩家退水值 (目前無用途) + 玩家彩金值 + 賞罰(正負值 若無預設0)');

            $table->string('FundRate')->nullable()
                ->comment('玩家彩金公積金比例 (目前無用途)');

            $table->string('GameDepartmentID')->nullable()
                ->comment('遊戲館別');

            $table->string('GameItem')->nullable()
                ->comment('遊戲遊戲項目');

            $table->string('GameType')->nullable()
                ->comment('遊戲類別 1. 真人遊戲，2.彩票，3.電子，4.體育，5.對戰，6.金融  7.電投');

            $table->string('JackpotScore')->nullable()
                ->comment('玩家彩金值');

            $table->string('Member')->nullable()
                ->comment('會員分桶ID+@+帳號');

            $table->string('MemberID')->nullable()
                ->comment('會員帳號');

            $table->string('MemberName')->nullable()
                ->comment('會員名稱');

            $table->string('NoRun')->nullable()
                ->comment('輪號');

            $table->string('NoActive')->nullable()
                ->comment('局號');

            $table->string('Odds')->nullable()
                ->comment('賠率');

            $table->string('OriginID')->nullable()
                ->comment('原始注單流水號 (預設null，若有改牌才有值)');

            $table->string('OriginBetRequestID')->nullable()
                ->comment('原始下注請求編號 (預設null，若有改牌才有值)');

            $table->string('LastBetRequestID')->nullable()
                ->comment('上筆下注請求編號 (預設null，若有改牌才有值)');

            $table->string('PortionRate')->nullable()
                ->comment('0 (目前無用途)');

            $table->string('ProviderID')->nullable()
                ->comment('遊戲廠商代碼');

            $table->string('PublicScore')->nullable()
                ->comment('玩家公點 (目前無用途)');

            $table->string('RebateRate')->nullable()
                ->comment('玩家退水值 (目前無用途)');

            $table->string('RewardScore')->nullable()
                ->comment('賞罰(正負值)，若無預設0');

            $table->string('ServerID')->nullable()
                ->comment('遊戲伺服器ID');

            $table->string('ServerName')->nullable()
                ->comment('遊戲伺服器名稱');

            $table->string('SettlementTime')->nullable()
                ->comment('結算時間');

            $table->string('StakeID')->nullable()
                ->comment('注區ID');

            $table->string('StakeName')->nullable()
                ->comment('注區名稱');

            $table->string('SubClientType')->nullable()
                ->comment('(目前無用途)');

            $table->string('ValidBetScore')->nullable()
                ->comment('有效押分(實貨量)');

            $table->string('WinScore')->nullable()
                ->comment('輸贏結果(正負值)，不含退水、彩金、玩家公點');

            $table->string('TimeInt')->nullable()
                ->comment('時間戳');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('ID');
            $table->index('Member');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_royal_game');
    }
}