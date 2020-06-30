<?php

use SuperPlatform\UnitedTicket\Models\SaGamingTicket;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class UnitedTicketTest extends BaseTestCase
{
    /**
     * 測試整合式注單的 migration 內容是否可正常使用
     *
     * @test
     */
    public function testRunningMigrationAndFactory()
    {
        factory(UnitedTicket::class, 10)->create();
        $this->assertEquals(UnitedTicket::count(), 10);
    }

    /**
     * 測試整合式注單的 migration 內容是否可正常使用
     *
     * @test
     */
//    public function testConvert()
//    {
//
//    }

    /**
     * 測試當將原生以 REPLACE INTO 方式寫入至資料庫
     *
     * @test
     */
    public function testRawTicketReplaceInto()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTickets = [
            [
                "BetID" => 894811793,
                "BetTime" => "2017-09-28T11:38:43.577",
                "PayoutTime" => "2017-09-28T11:38:45.617",
                "GameID" => 95504503668752,
                "HostID" => 401,
                "HostName" => "老虎機",
                "GameType" => "slot",
                "Set" => 0,
                "Round" => 0,
                "BetType" => 30,
                "BetAmount" => 30,
                "Rolling" => 30,
                "Detail" => "EG-SLOT-A020",
                "GameResult" => '',
                "ResultAmount" => -25,
                "Balance" => 988.1,
                "State" => 1,
                'Username' => '30GRAAA680505',
                'uuid' => '0b4bc40e-22ec-314d-8785-c044f55210f8',
            ],
            [
                "BetID" => 894811799,
                "BetTime" => "2017-09-28T11:38:43.577",
                "PayoutTime" => "2017-09-28T11:38:45.617",
                "GameID" => 95504503668752,
                "HostID" => 401,
                "HostName" => "老虎機",
                "GameType" => "slot",
                "Set" => 0,
                "Round" => 0,
                "BetType" => 30,
                "BetAmount" => 30,
                "Rolling" => 30,
                "Detail" => "EG-SLOT-A020",
                "GameResult" => '',
                "ResultAmount" => -25,
                "Balance" => 988.1,
                "State" => 1,
                'Username' => '30GRAAA680505',
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741e",
            ]
        ];

        // -----------
        //   Act
        // ----------
        SaGamingTicket::replace($rawTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(SaGamingTicket::count(), 2);
    }
}