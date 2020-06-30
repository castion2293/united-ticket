<?php

use SuperPlatform\UnitedTicket\Fetchers\NineKLottery2Fetcher;
use SuperPlatform\UnitedTicket\Models\NineKLottery2Ticket;

class NineKLottery2FetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "BossID" => "develop",
        "MemberAccount" => "TT8E7D7040",
        "TypeCode" => "JSPK10",
        "GameDate" => "2019-10-05",
        "GameTime" => "14:21:15",
        "GameNum" => "1910050689",
        "GameResult" => null,
        "WagerID" => 64165777,
        "WagerDate" => "2019-10-05 14:20:09",
        "BetItem" => "第五名 03 9.850",
        "TotalAmount" => 10,
        "BetAmount" => 10,
        "PayOff" => "0.00",
        "Result" => "X",
        "uuid" => "a71d40a3-adb0-365a-99f4-ec55e85ec729"
    ];

    protected $rawTicketData_2 = [
        "BossID" => "develop",
        "MemberAccount" => "TT8E7D7040",
        "TypeCode" => "JSPK10",
        "GameDate" => "2019-10-05",
        "GameTime" => "14:21:15",
        "GameNum" => "1910050689",
        "GameResult" => null,
        "WagerID" => 64165780,
        "WagerDate" => "2019-10-05 14:20:09",
        "BetItem" => "第五名 04 9.850",
        "TotalAmount" => 10,
        "BetAmount" => 10,
        "PayOff" => "0.00",
        "Result" => "X",
        "uuid" => "0022b80b-e136-3b1e-9262-4c441f0d5d19",
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
        $ticket = new NineKLottery2Ticket($this->rawTicketData_1);
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
        $fetcher = new NineKLottery2Fetcher([]);

        try {
            $response = $fetcher->setTimeSpan('2019-09-05 00:00:00', '2019-09-05 23:00:00')->capture();
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
        //   Act
        // -----------
        $fetcher = new NineKLottery2Fetcher([]);

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
        $fetcher = new NineKLottery2Fetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([NineKLottery2Ticket::class, 'replace'], $fetchTickets);

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
        $fetcher = new NineKLottery2Fetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([NineKLottery2Ticket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['Result'] = "W";

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
        $fetcher = new NineKLottery2Fetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([NineKLottery2Ticket::class, 'replace'], $fetchTickets);

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