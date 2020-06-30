<?php

use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\RenNiYingFetcher;
use SuperPlatform\UnitedTicket\Models\RenNiYingTicket;

class RenNiYingFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "id" => "7733133",
        "status" => 0,
        "userId" => "TT5C004998",
        "created" => "2019-10-07T15:04:35",
        "gameId" => 8,
        "roundId" => "20191007025",
        "place" => "亞軍",
        "guess" => "雙",
        "odds" => 1.98,
        "money" => 10.0,
        "result" => 0.0,
        "playerRebate" => 0.0,
        "uuid" => "4f23885d-31a1-3245-ab8e-15a0a41d07e0",
    ];

    protected $rawTicketData_2 = [
        "id" => "7733132",
        "status" => 0,
        "userId" => "TT5C004998",
        "created" => "2019-10-07T15:04:35",
        "gameId" => 8,
        "roundId" => "20191007025",
        "place" => "第四名",
        "guess" => "虎",
        "odds" => 1.98,
        "money" => 10.0,
        "result" => 0.0,
        "playerRebate" => 0.0,
        "uuid" => "a62bf519-6378-317b-a1be-cd9d0411dc96",
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
        $ticket = new RenNiYingTicket($this->rawTicketData_1);
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
        $fetcher = new RenNiYingFetcher([]);

        try {
            $response = $fetcher->setTimeSpan('', '')->capture();
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
        $fetcher = new RenNiYingFetcher([]);

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
        $fetcher = new RenNiYingFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RenNiYingTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new RenNiYingFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RenNiYingTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['status'] = 1;

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
        $fetcher = new RenNiYingFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RenNiYingTicket::class, 'replace'], $fetchTickets);

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