<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\AllBetFetcher;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;

class AllBetFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        //客戶用戶名
        "client" => "tt8e7d7040",
        //[UK]注單編號
        "betNum" => 3076654354106694,
        //遊戲局編號
        "gameRoundId" => 307665435,
        //遊戲類型
        "gameType" => 301,
        //投注時間
        "betTime" => "2019-10-02 10:37:19",
        //投注金額
        "betAmount" => 20,
        //有效投注金額
        "validAmount" => 20,
        //輸贏金額
        "winOrLoss" => 20,
        //注單狀態(0:正常 1:不正常)
        "state" => 0,
        //投注類型
        "betType" => 2002,
        //開牌結果
        "gameResult" => "{205},{411}",
        //遊戲結束時間
        "gameRoundEndTime" => "2019-10-02 10:37:48",
        //遊戲開始時間
        "gameRoundStartTime" => "2019-10-02 10:37:15",
        //桌台名稱
        "tableName" => "D001",
        //桌台類型 (100:非免佣 1:免佣)
        "commission" => 100,

        "uuid"=> "52c1198f-c608-3d44-b03a-91fa3dd4802d",

        "username" => "TT8E7D7040",
    ];

    protected $rawTicketData_2 = [
        //客戶用戶名
        "client" => "tt8e7d7040",
        //[UK]注單編號
        "betNum" => 3076680695895387,
        //遊戲局編號
        "gameRoundId" => 307668069,
        //遊戲類型
        "gameType" => 301,
        //投注時間
        "betTime" => "2019-10-02 11:21:28",
        //投注金額
        "betAmount" => 50,
        //有效投注金額
        "validAmount" => 50,
        //輸贏金額
        "winOrLoss" => -50,
        //注單狀態(0:正常 1:不正常)
        "state" => 0,
        //投注類型
        "betType" => 2002,
        //開牌結果
        "gameResult" => "{309},{204}",
        //遊戲結束時間
        "gameRoundEndTime" => "2019-10-02 11:21:40",
        //遊戲開始時間
        "gameRoundStartTime" => "2019-10-02 11:21:09",
        //桌台名稱
        "tableName" => "D001",
        //桌台類型 (100:非免佣 1:免佣)
        "commission" => 100,

        "uuid" => "5d04c3c4-bd7a-33b4-b3eb-35cc71391620",

        "username" => "TT8E7D7040",
    ];

    protected $username = '_aljjjjt61';

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Act
        // -----------
        $ticket = new AllBetTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;
        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);

        $this->assertArrayHasKey('uuid', $datas);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * 測試呼叫 API 成功
     *
     * @test
     */
    public function testSuccessCapture()
    {
        $from = '2020-04-28 00:00:00';
        $to = '2020-04-28 23:59:59';

        // -----------
        //   Act
        // -----------
        $fetcher = new AllBetFetcher([
            'username' => $this->username
        ]);
        try {
            $response = $fetcher->setTimeSpan($from, $to)->capture();
            $this->assertArrayHasKey('tickets', $response);
        } catch (ApiCallerException $exc) {
            // -----------
            //   Assert
            // -----------
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $exc->getMessage());
            $this->assertEquals(true, is_array($exc->response()));
        }
    }

    /**
     * 測試撈歐博電子注單
     *
     * ＠test
     */
    public function testFetchEGameTicket()
    {
        $from = '2019-12-05 00:00:00';
        $to = '2019-12-05 23:59:59';

        // -----------
        //   Act
        // -----------
        $fetcher = new AllBetFetcher([
            'e_game_type' => 'af'
        ]);
        try {
            $response = $fetcher->setTimeSpan($from, $to)->capture();
            $this->assertArrayHasKey('tickets', $response);
        } catch (ApiCallerException $exc) {
            // -----------
            //   Assert
            // -----------
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $exc->getMessage());
            $this->assertEquals(true, is_array($exc->response()));
        }
    }

    /**
     * 測試撈第一張，然後比對
     *
     * @test
     */
    public function testFetchFirstTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new AllBetFetcher([
            'username' => $this->username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(1, count(array_get($tickets, 'tickets')));
    }

    /**
     * 測試撈同樣第一張，然後比對會找出同樣的結果，所以不會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new AllBetFetcher([
            'username' => $this->username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([AllBetTicket::class, 'replace'], $fetchTickets);

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(0, count(array_get($tickets, 'tickets')));
    }

    /**
     * 測試撈同樣第一張，然後比對會找出不一樣的結果，所以會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicketWithDifferentState()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new AllBetFetcher([
            'username' => $this->username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([AllBetTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['state'] = 1;

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(1, count(array_get($tickets, 'tickets')));
    }

    /**
     * 測試撈第2張，然後比對，會產出須轉換的是第二張單
     *
     * @test
     */
    public function testFetchSecondTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new AllBetFetcher([
            'username' => $this->username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([AllBetTicket::class, 'replace'], $fetchTickets);

        // 出現第二張單
        $fetchTickets = [
            $this->rawTicketData_1,
            $this->rawTicketData_2
        ];

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $ticket = array_get($tickets, 'tickets');
        $this->assertEquals(1, count($ticket));
        $this->assertEquals($this->rawTicketData_2['uuid'], array_first($ticket)['uuid']);
    }
}