<?php


namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use SuperPlatform\UnitedTicket\Models\VsLotteryTicket;

class VsLotteryFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
          "FetchId" => 91412,
          "TrId" => 600392,
          "TrDetailID" => 10774459,
          "TrDate" => "2019-11-21T18:06:35.843",
          "UserName" => "T93Tester03",
          "DrawDate" => "2019-11-21T00:00:00",
          "MarketName" => "4D (Thu)",
          "BetType" => "4A",
          "BetNo" => 5026,
          "Turnover" => 1,
          "CommAmt" => 0.25,
          "NetAmt" => 0.75,
          "WinAmt" => -1,
          "Stake" => 1,
          "StrikeCount" => 0,
          "Odds1" => 6800,
          "Odds2" => 0,
          "Odds3" => 0,
          "Odds4" => 0,
          "Odds5" => 0,
          "CurCode" => "VND",
          "WinLossStatus" => "L",
          "IsPending" => "false",
          "IsCancelled" => "false",
          "LastChangeDate" => "2019-11-21T19:26:00.593",
          "uuid" => "a3cda7ea-944d-34ff-8353-ca343d34e1ac",
    ];

    protected $rawTicketData_2 = [
        "FetchId" => 91415,
        "TrId" => 600332,
        "TrDetailID" => 10771459,
        "TrDate" => "2019-11-21T18:06:55.843",
        "UserName" => "T93Tester03",
        "DrawDate" => "2019-11-21T00:00:00",
        "MarketName" => "4D (Thu)",
        "BetType" => "4A",
        "BetNo" => 5022,
        "Turnover" => 1,
        "CommAmt" => 0.25,
        "NetAmt" => 0.75,
        "WinAmt" => -1,
        "Stake" => 1,
        "StrikeCount" => 0,
        "Odds1" => 800,
        "Odds2" => 0,
        "Odds3" => 0,
        "Odds4" => 0,
        "Odds5" => 0,
        "CurCode" => "VND",
        "WinLossStatus" => "W",
        "IsPending" => "false",
        "IsCancelled" => "false",
        "LastChangeDate" => "2019-11-21T20:26:00.593",
        "uuid" => "a3cda7ea-944d-34ff-8353-ca343d34e1ac",
    ];

    /**
     * 測試當原生注單注入時，是否會為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Act
        // -----------
        $ticket = new VsLotteryTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;

        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
        $this->assertEquals(1, $isUUID3);
        $this->assertArrayHasKey('uuid', $datas);
    }

    /**
     * 測試呼叫抓取 (Capture) 的動作成功，並取得成功的回應結果的案例
     *
     * @test
     */
    public function testSuccessCapture()
    {
        // -----------
        //   Act
        // -----------
        $fetcher = new VsLotteryFetcher([]);

        try {
            $response = $fetcher->setTimeSpan('2019-08-28 00:00:00', '2019-08-28 23:59:59')->capture();
            $this->assertArrayHasKey('tickets', $response);
        } catch (\SuperPlatform\ApiCaller\Exceptions\ApiCallerException $exc) {
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
        $fetcher = new VsLotteryFetcher([]);

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
        $fetcher = new VsLotteryFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([VsLotteryTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new VsLotteryFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([VsLotteryTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['status'] = 'COMPLETED';

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
        $fetcher = new VsLotteryFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([VsLotteryTicket::class, 'replace'], $fetchTickets);

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