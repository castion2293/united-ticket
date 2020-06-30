<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsHongChowTable extends Migration
{
    protected $table = 'raw_tickets_hong_chow';

    public function up()
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
            $table->integer('bet_id')->nullable()
                ->comment('注单号');

            $table->string('user_name')->nullable()
                ->comment('电竞用户名');

            $table->integer('bettype')->nullable()
                ->comment('0:单注 1:串注');

            $table->decimal('betamount', 10, 2)->nullable()
                ->comment('投注金额');

            $table->decimal('refundamount', 10, 2)->nullable()
                ->comment('退款金额');

            $table->decimal('returnamount', 10, 2)->nullable()
                ->comment('派奖金额 =0 未中奖 =null 无赛果');

            $table->dateTime('bettime')->nullable()
                ->comment('投注时间 格式如：2017-09-09 12:12:12');

            $table->string('betip')->nullable()
                ->comment('下注 IP');

            $table->integer('betsrc')->nullable()
                ->comment('投注类型 1：pc 下注 2：移动下注');

            $table->integer('part_id')->nullable()
                ->comment('投注选项 ID');

            $table->string('part_name')->nullable()
                ->comment('投注选项名称');

            $table->integer('part_odds')->nullable()
                ->comment('投注配置，放大 1000 倍 如：1200 表示赔率 1.2');

            $table->integer('game_id')->nullable()
                ->comment('游戏 ID');

            $table->string('game_name')->nullable()
                ->comment('游戏名称');

            $table->string('match_name')->nullable()
                ->comment('赛事名称');

            $table->string('race_name')->nullable()
                ->comment('比赛名称');

            $table->integer('han_id')->nullable()
                ->comment('盘口 ID');

            $table->string('han_name')->nullable()
                ->comment('盘口名称');

            $table->string('team1_name')->nullable()
                ->comment('队伍 1 名称');

            $table->string('team2_name')->nullable()
                ->comment('队伍 2 名称');

            $table->integer('round')->nullable()
                ->comment('局数');

            $table->integer('result')->nullable()
                ->comment('开奖结果，对应 part_id 字段');

            $table->string('reckondate')->nullable()
                ->comment('结算时间（对应查询时间）');

            $table->integer('status2')->nullable()
                ->comment('51:无效单 52:未审核 53:未通过 54:已审核 55:系统无效单 56: 中奖 57:无赛果 58:未中奖');

            $table->integer('sync_version')->nullable()
                ->comment('数据版本号');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['bet_id']);
            // 指定查詢索引
            $table->index('user_name');
            $table->index('bet_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}