<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsWinnerSportTable extends Migration
{
    private $table = 'raw_tickets_winner_sport';

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
            $table->string('id')->nullable()
                ->comment('注單id');

            $table->string('mrid')->nullable()
                ->comment('紀錄id => maxModId');

            $table->integer('pr')->nullable()
                ->comment('0=非過關單，1=過關單');

            $table->integer('status')->nullable()
                ->comment('注單狀態 1=有效注單，3=因賽事結果取消的注單，其他都為註銷的注單');

            $table->string('stats')->nullable()
                ->comment('注單狀態(文字)');

            $table->string('mark')->nullable()
                ->comment('注單標記');

            $table->integer('meid')->nullable()
                ->comment('會員id');

            $table->string('meusername')->nullable()
                ->comment('會員帳號');

            $table->string('meusername1')->nullable()
                ->comment('會員帳號(不含前墜碼)');

            $table->integer('gold')->nullable()
                ->comment('下注金額');

            $table->integer('gold_c')->nullable()
                ->comment('下注有效金額');

            $table->string('io')->nullable()
                ->comment('賠率(不含本金)');

            $table->string('result')->nullable()
                ->comment('注單結果 (W=全贏，L=全輸，WW=中洞贏，LL=中洞輸，WL=中洞全退，N=因賽事結果取消，NC=註銷, 空值=還沒有結果)');

            $table->float('meresult', 10, 2)->nullable()
                ->comment('會員結果');

            $table->string('gtype')->nullable()
                ->comment('成數球種(代號)');

            $table->string('rtype')->nullable()
                ->comment('玩法(代號)');

            $table->string('g_title')->nullable()
                ->comment('球種(中文)');

            $table->string('r_title')->nullable()
                ->comment('玩法');

            $table->string('l_sname')->nullable()
                ->comment('聯盟名');

            $table->integer('orderdate')->nullable()
                ->comment('歸帳日期(eg. 20160610)');

            $table->string('IP')->nullable()
                ->comment('下注IP');

            $table->datetime('added_date')->nullable()
                ->comment('下注時間');

            $table->datetime('modified_date')->nullable()
                ->comment('紀錄修改時間');

            $table->text('detail_1')->nullable()
                ->comment('注單內容(有加html tag)');

            $table->text('detail')->nullable()
                ->comment('注單詳細內容');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定查詢索引
            $table->index('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
}