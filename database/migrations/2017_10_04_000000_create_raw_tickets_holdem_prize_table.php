<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawTicketsHoldemPrizeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_tickets_holdem_prize', function (Blueprint $table) {
            // [PK] 資料識別碼
            $table->uuid('uuid')
                ->comment('資料識別碼');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            $table->integer('PlatformID')
                ->comment('平台代號');

            $table->string('MemberAccount', 30)
                ->comment('玩家帳號');

            $table->dateTime('Time')
                ->comment('時間');

            $table->string('JPId', 40)
                ->comment('[UK] 彩金唯一碼');

            $table->decimal('BeforePoints', 10, 2)
                ->comment('原本點數');

            $table->decimal('ChangePoints', 10, 2)
                ->comment('增減點數');

            $table->decimal('AfterPoints', 10, 2)
                ->comment('增減最後點數');

            $table->string('JPName', 20)
                ->comment('彩金紀錄');

            // === 索引約束 ===
            // 指定主鍵
            $table->primary(['uuid']);
            // 指定聯合鍵
            $table->unique(['JPId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_tickets_holdem_prize');
    }
}
