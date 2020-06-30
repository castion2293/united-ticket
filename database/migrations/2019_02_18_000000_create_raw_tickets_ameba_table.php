<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsAmebaTable extends Migration
{
    protected $table = 'raw_tickets_ameba';

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
            $table->string('account_name')->nullable()
                ->comment('玩家的账户名称');

            $table->string('currency')->nullable()
                ->comment('玩家的货币');

            $table->integer('game_id')->nullable()
                ->comment('游戏ID');

            $table->string('round_id')->nullable()
                ->comment('每局游戏的唯一ID');

            $table->boolean('free')->nullable()
                ->comment('当本局游戏免费时为true, 免费转即是本局投注额不会从玩家钱包中扣除');

            $table->string('bet_amt')->nullable()
                ->comment('本局游戏的投注额');

            $table->string('payout_amt')->nullable()
                ->comment('本局游戏的派彩金额');

            $table->string('completed_at')->nullable()
                ->comment('本局游戏的结束时间。（UTC时区，+00:00）格式为: YYYY-MM-DDThh:mm:ssTZD');

            $table->string('rebate_amt')->nullable()
                ->comment('返水开启时 返水至玩家余额, 应大于或等于0');

            $table->string('jp_pc_con_amt')->nullable()
                ->comment('彩池开启时 玩家货币下的彩池累积金额');

            $table->string('jp_jc_con_amt')->nullable()
                ->comment('彩池开启时 彩池货币下的彩池累积金额');

            $table->string('jp_win_id')->nullable()
                ->comment('彩池中奬时 彩池中奖编号');

            $table->string('jp_pc_win_amt')->nullable()
                ->comment('彩池中奬时 玩家货币下的派彩金额');

            $table->string('jp_jc_win_amt')->nullable()
                ->comment('彩池中奬时 彩池货币下的派彩金额');

            $table->string('jp_win_lv')->nullable()
                ->comment('彩池中奬时 彩池中奖级别');

            $table->boolean('jp_direct_pay')->nullable()
                ->comment('彩池中奬时 若把jp_pc_win_amt 直接存入玩家钱包则为true');

            $table->string('prize_type')->nullable()
                ->comment('本局游戏中获得红包时 rpcash – 红包的奖励为现金 rpfreespin – 红包的奖励为免费转');

            $table->string('prize_amt')->nullable()
                ->comment('本局游戏中获得红包时 如果红包类型为rpcash，则这个值是总奖励金额。如果红包类型为 rpfreespin，则这个值是总奖励局数');

            $table->integer('site_id')->nullable()
                ->comment('request 参数中使用group时 这局游戏的营运商识别代码');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);

            // TODO: 指定聯合鍵 與 指定查詢索引 需依照不同遊戲商調整
            // 指定聯合鍵
            $table->unique(['round_id']);
            // 指定查詢索引
            $table->index('account_name');
            $table->index('round_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
}