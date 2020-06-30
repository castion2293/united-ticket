<?php


use SuperPlatform\UnitedTicket\Fetchers\BoboPokerFetcher;
use SuperPlatform\UnitedTicket\Models\BoboPokerTicket;

class BoboPokerFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        'account' => 'TT8E7D7040',
        'betId' => 83256792,
        'gameNumber' => 'DB20191001143796',
        'gameName' => 'dice-bao',
        'betDetailId' => 86546207,
        'betAmt' => 3000,
        'earn' => -3000,
        'content' => 'count-4',
        'betTime' => '20191001143710',
        'payoutTime' => '20191001143742',
        'status' => '0',
        'uuid' => '2cfde239-6114-3341-8175-6f74ef61c9ff'
    ];

    protected $rawTicketData_2 = [
        'account' => 'TT8E7D7040',
        'betId' => 83256741,
        'gameNumber' => 'ARL191001141796',
        'gameName' => 'american-roulette',
        'betDetailId' => 86546044,
        'betAmt' => 1000,
        'earn' => -1000,
        'content' => 'column_2',
        'betTime' => '20191001141736',
        'payoutTime' => '20191001141740',
        'status' => '0',
        'uuid' => 'ac991554-3f46-3fb0-8809-b884d7e2c8fe'
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
        $ticket = new BoboPokerTicket($this->rawTicketData_1);
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
        $fetcher = new BoboPokerFetcher([]);

        try {
            $response = $fetcher->setTimeSpan('2019-09-19 17:00:00', '2019-09-19 17:59:59')->capture();
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
        $fetcher = new BoboPokerFetcher([]);

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
        $fetcher = new BoboPokerFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([BoboPokerTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new BoboPokerFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([BoboPokerTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['status'] = "1";

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
        $fetcher = new BoboPokerFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([BoboPokerTicket::class, 'replace'], $fetchTickets);

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