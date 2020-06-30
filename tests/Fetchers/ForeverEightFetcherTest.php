<?php

use SuperPlatform\UnitedTicket\Fetchers\ForeverEightFetcher;
use SuperPlatform\UnitedTicket\Models\ForeverEightTicket;

class ForeverEightFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "BillNo" => "5304645",
        "GameID" => "1008",
        "BetValue" => "1.0000",
        "NetAmount" => "5.0000",
        "SettleTime" => "2019-10-15 15:49:38",
        "AgentsCode" => "CS0166",
        "Account" => "TT02F73CC4",
        "TicketStatus" => "LOSE",
        "uuid" => "92b8d5a5-d2fb-350a-8bc0-6b73917eca37"
    ];

    protected $rawTicketData_2 = [
        "BillNo" => "5304644",
        "GameID" => "1008",
        "BetValue" => "1.0000",
        "NetAmount" => "10.0000",
        "SettleTime" => "2019-10-15 14:49:38",
        "AgentsCode" => "CS0166",
        "Account" => "TT02F73CC4",
        "TicketStatus" => "WIN",
        "uuid" => "92b8d5a5-d2fb-350a-8bc0-6b73917eca37"
    ];

    /**
     * 測試當原生注單注入時，是否會為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        $ticket = new ForeverEightTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;

        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);

        $this->assertArrayHasKey('uuid', $datas);
        $this->assertEquals(1, $isUUID3);
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
        $fetcher = new ForeverEightFetcher([]);

        try {
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2019-09-16 00:00:00',
                    '2019-09-17 00:00:00'
                )->capture();
            $this->assertArrayHasKey('tickets', $aTicketsMerged);
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
        $fetcher = new ForeverEightFetcher([]);

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
     * 測試撈同樣第一張，然後比對會找出不一樣的結果，所以會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicketWithDifferentState()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new ForeverEightFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([ForeverEightTicket::class, 'replace'], $fetchTickets);

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
     * 測試撈第2張，然後比對，會產出須轉換的是第二張單
     *
     * @test
     */
    public function testFetchSecondTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new ForeverEightFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([ForeverEightTicket::class, 'replace'], $fetchTickets);

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