<?php

use SuperPlatform\UnitedTicket\Converters\SaGamingConverter;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class SaGamingConvertTest extends BaseTestCase
{
    private $rawTickets = [
        'tickets' => [
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
                "State" => "true",
                'Username' => '30GRAAA680505',
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741e",
            ],
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
                "State" => "true",
                'Username' => '30GRAAA680505',
                'uuid' => '0b4bc40e-22ec-314d-8785-c044f55210f8',
            ],
        ],
    ];

    /**
     * 測試將原生注單轉換成整合注單
     *
     * @test
     */
    public function testConvert()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTickets = $this->rawTickets;

        // -----------
        //   Act
        // -----------
        $converter = new SaGamingConverter();
        $unitedTickets = $converter->transform($rawTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(count($unitedTickets), 2);

    }

    /**
     * 測試將原生注單轉換成整合注單
     *
     * @test
     */
    public function testUnitedTicketsReplaceInto()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTickets = $this->rawTickets;

        // -----------
        //   Act
        // -----------
        $converter = new SaGamingConverter();
        $unitedTickets = $converter->transform($rawTickets);
        UnitedTicket::replace($unitedTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(UnitedTicket::count(), 2);

    }
}