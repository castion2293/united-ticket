<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUnitedTicketsAllotRange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        for ($i = 0; $i < 13; $i++) {
//            $sql = "ALTER TABLE `united_tickets` ADD `depth_{$i}_allot_start` DATETIME NULL COMMENT '佔成版本號起始時間' AFTER `depth_{$i}_ratio`;";
//            DB::statement($sql);
//            $sq2 = "ALTER TABLE `united_tickets` ADD `depth_{$i}_allot_end` DATETIME NULL COMMENT '佔成版本號結束時間' AFTER `depth_{$i}_allot_start`;";
//            DB::statement($sq2);
//        }
        DB::statement('ALTER TABLE
            `united_tickets` 
            ADD `depth_12_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_12_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_11_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_11_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_10_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_10_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_9_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_9_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_8_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_8_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_7_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_7_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_6_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_6_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_5_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_5_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_4_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_4_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_3_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_3_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_2_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_2_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_1_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_1_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_0_allot_start` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`,
            ADD `depth_0_allot_end` DATETIME NULL DEFAULT NULL AFTER `depth_12_rebate`;'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        for ($i = 0; $i < 13; $i++) {
//            $sql = "ALTER TABLE `united_tickets` DROP `depth_{$i}_allot_start`;";
//            DB::statement($sql);
//            $sql2 = "ALTER TABLE `united_tickets` DROP `depth_{$i}_allot_end`;";
//            DB::statement($sql2);
//        }

        DB::statement('ALTER TABLE
            `united_tickets` 
            DROP `depth_12_allot_start`,
            DROP `depth_12_allot_end`,
            DROP `depth_11_allot_start`,
            DROP `depth_11_allot_end`,
            DROP `depth_10_allot_start`,
            DROP `depth_10_allot_end`,
            DROP `depth_9_allot_start`,
            DROP `depth_9_allot_end`,
            DROP `depth_8_allot_start`,
            DROP `depth_8_allot_end`,
            DROP `depth_7_allot_start`,
            DROP `depth_7_allot_end`,
            DROP `depth_6_allot_start`,
            DROP `depth_6_allot_end`,
            DROP `depth_5_allot_start`,
            DROP `depth_5_allot_end`,
            DROP `depth_4_allot_start`,
            DROP `depth_4_allot_end`,
            DROP `depth_3_allot_start`,
            DROP `depth_3_allot_end`,
            DROP `depth_2_allot_start`,
            DROP `depth_2_allot_end`,
            DROP `depth_1_allot_start`,
            DROP `depth_1_allot_end`,
            DROP `depth_0_allot_start`,
            DROP `depth_0_allot_end`;'
        );
    }
}
