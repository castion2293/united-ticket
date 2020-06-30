<?php

use SuperPlatform\UnitedTicket\Fetchers\QTechFetcher;
use SuperPlatform\UnitedTicket\Models\QTechTicket;

class QTechFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "id" => "5d9aadaffeaff60001368b82",
        "status" => "PENDING",
        "totalBet" => "20.00",
        "totalPayout" => "0.00",
        "totalBonusBet" => "0.00",
        "currency" => "TWD",
        "initiated" => "2019-10-07T11:14:55.527+08:00[Asia/Shanghai]",
        "completed" => "2019-10-07T11:14:56.272+08:00[Asia/Shanghai]",
        "operatorId" => "qt966",
        "playerId" => "TT8E7D7040",
        "device" => "DESKTOP",
        "gameProvider" => "1x2",
        "gameId" => "1x2-eraofgods",
        "gameCategory" => "CASINO/SLOT/5REEL",
        "gameClientType" => "HTML5",
        "uuid" => "a3cda7ea-944d-34ff-8353-ca343d34e1ac",
    ];

    protected $rawTicketData_2 = [
        "id" => "5d9aadb5feaff60001368b93",
        "status" => "PENDING",
        "totalBet" => "20.00",
        "totalPayout" => "110.00",
        "totalBonusBet" => "0.00",
        "currency" => "TWD",
        "initiated" => "2019-10-07T11:15:01.307+08:00[Asia/Shanghai]",
        "completed" => "2019-10-07T11:15:02.061+08:00[Asia/Shanghai]",
        "operatorId" => "qt966",
        "playerId" => "TT8E7D7040",
        "device" => "DESKTOP",
        "gameProvider" => "1x2",
        "gameId" => "1x2-eraofgods",
        "gameCategory" => "CASINO/SLOT/5REEL",
        "gameClientType" => "HTML5",
        "uuid" => "77915e64-36b2-3d05-ad01-d64f220f087f"
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
        $ticket = new QTechTicket($this->rawTicketData_1);
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
        $fetcher = new QTechFetcher([]);

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
        $fetcher = new QTechFetcher([]);

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
        $fetcher = new QTechFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([QTechTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new QTechFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([QTechTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new QTechFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([QTechTicket::class, 'replace'], $fetchTickets);

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