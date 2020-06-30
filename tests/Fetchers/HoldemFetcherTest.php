<?php

use Carbon\Carbon;
use SuperPlatform\UnitedTicket\Fetchers\HoldemFetcher;
use SuperPlatform\UnitedTicket\Models\HoldemTicket;
use SuperPlatform\UnitedTicket\Models\HoldemSlotTicket;

class HoldemFetcherTest extends BaseTestCase
{
    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTicketData = [
            "PlayTime" => "2017-10-18 01:56:55",
            "RoundCode" => "n1_1710180156005",
            "RoundId" => 1366,
            "PlatformID" => 10,
            "MemberAccount" => '10$ANB6111',
            "OriginalPoints" => 61121.0000,
            "Bet" => 200.0000,
            "WinLose" => -200.0000,
            "LastPoints" => 60921.0000,
            "ServicePoints" => 5.0000,
            "HandselServicePoints" => 1.0000,
            "GSLogPath" => "http://poker.sp5588.cc/GSLog/2017-10-18/n1_1710180156005.txt"
        ];

        // -----------
        //   Act
        // -----------
        $ticket = new HoldemTicket($rawTicketData);
        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * 測試彩金拉霸原注單注入，為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testSlotTicketUuid()
    {
        // -----------
        //   Arrange
        // -----------
        $slotTicketData = [
            "Time" => "2017-10-24 09:48:29",
            "PlatformID" => "25",
            "MemberAccount" => '25$1sf6637',
            "JPId" => 1710240948001,
            "BeforePoints" => 2053694.00,
            "ChangePoints" => 0.00,
            "AfterPoints" => 2053694.00,
            "JPName" => "1,2,2",
        ];

        // -----------
        //   Act
        // -----------
        $ticket = new HoldemSlotTicket($slotTicketData);
        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     *
     * 測試呼叫抓取 (Capture) 的動作成功，並取得成功的回應結果的案例
     *
     * @test
     */
//    public function testSuccessCapture()
//    {
//        // -----------
//        //   Arrange
//        // -----------
//        $hours = 24; // 1小時
//        $dt = Carbon::now();
//        $cp = $dt->copy();
//
//        // -----------
//        //   Act
//        // -----------
//        $fetcher = App::make(HoldemFetcher::class);
//        $response = $fetcher->setTimeSpan($cp->subHours($hours)->toDateTimeString(), $dt->toDateTimeString())->capture();
//
//        // -----------
//        //   Assert
//        // -----------
//        $this->assertArrayHasKey('tickets', $response);
//    }
}