<?php

use SuperPlatform\UnitedTicket\Converters\HoldemConverter;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class HoldemConvertTest extends BaseTestCase
{
    private $rawTickets = [
        'tickets' => [
            [
                "PlayTime" => "2017-10-24 11:52:45",
                "RoundCode" => "n3_1710241152003",
                "RoundId" => 1699,
                "PlatformID" => 25,
                "MemberAccount" => '25$1sf6637',
                "OriginalPoints" => 1951014.0000,
                "Bet" => 1000.0000,
                "WinLose" => -1000.0000,
                "LastPoints" => 1950014.0000,
                "ServicePoints" => 25.0000,
                "HandselServicePoints" => 5.0000,
                "GSLogPath" => "http://poker.sp5588.cc/GSLog/2017-10-24/n3_1710241152003.txt",
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741e"
            ],
            [
                "PlayTime" => "2017-10-24 11:52:26",
                "RoundCode" => "n1_1710241152002",
                "RoundId" => 329,
                "PlatformID" => 10,
                "MemberAccount" => '10$ANB6115',
                "OriginalPoints" => 21348.0000,
                "Bet" => 200.0000,
                "WinLose" => -200.0000,
                "LastPoints" => 21148.0000,
                "ServicePoints" => 5.0000,
                "HandselServicePoints" => 1.0000,
                "GSLogPath" => "http://poker.sp5588.cc/GSLog/2017-10-24/n1_1710241152002.txt",
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741a"
            ],
        ],
        'slot_tickets' => [
            [
                "Time" => "2017-10-24 11:53:56",
                "PlatformID" => 25,
                "MemberAccount" => "25$1sf6637",
                "JPId" => 1710241153000,
                "BeforePoints" => 1957534.00,
                "ChangePoints" => 0.00,
                "AfterPoints" => 1957534.00,
                "JPName" => "1,2,1",
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741b"
            ],
            [
                "Time" => "2017-10-24 11:54:02",
                "PlatformID" => 25,
                "MemberAccount" => "25$1sf6637",
                "JPId" => 1710241154000,
                "BeforePoints" => 1957534.00,
                "ChangePoints" => 0.00,
                "AfterPoints" => 1957534.00,
                "JPName" => "0,0,0;1,0,1",
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741c"
            ]
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
        $converter = new HoldemConverter();
        $unitedTickets = $converter->transform($rawTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(count($unitedTickets), 4);

    }

    /**
     * 測試將原生注單、彩金拉霸紀錄轉換成整合注單
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
        $converter = new HoldemConverter();
        $unitedTickets = $converter->transform($rawTickets);
        UnitedTicket::replace($unitedTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(UnitedTicket::count(), 4);

    }
}