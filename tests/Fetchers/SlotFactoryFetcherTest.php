<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\SlotFactoryFetcher;
use SuperPlatform\UnitedTicket\Models\SlotFactoryTicket;

class SlotFactoryFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "AccountID" => "TT8E7D7040",
        "RoundID" => "39ae132b-5683-49e1-a877-bfd115e9d602",
        "TransactionID" => "ac105f44-0e5a-446e-8baf-c33eed7c9257",
        "GameName" => "SnakesAndLadders",
        "SpinDate" => "2019-11-04 02:51:44",
        "Currency" => "THB",
        "Lines" => 9,
        "LineBet" => "100",
        "TotalBet" => "900",
        "CashWon" => "0",
        "GambleGames" => false,
        "FreeGames" => false,
        "FreeGamePlayed" => 0,
        "FreeGameRemaining" => 0,
        "uuid" => "c6b88be1-1260-3aed-9472-7e28c9ffe984",
    ];

    protected $rawTicketData_2 = [
        "AccountID" => "TT8E7D7040",
        "RoundID" => "a66b010f-b711-4797-b62a-f51b54bacacb",
        "TransactionID" => "88b8638b-a2bd-4e1e-9946-31f26d4b0a5d",
        "GameName" => "SnakesAndLadders",
        "SpinDate" => "2019-11-04 02:51:35",
        "Currency" => "THB",
        "Lines" => 9,
        "LineBet" => "100",
        "TotalBet" => "900",
        "CashWon" => "4300",
        "GambleGames" => false,
        "FreeGames" => false,
        "FreeGamePlayed" => 0,
        "FreeGameRemaining" => 0,
        "uuid" => "a34e0e62-caa9-3a80-a627-544acf710d63",
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
        $ticket = new SlotFactoryTicket($this->rawTicketData_1);
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
        $fetcher = new SlotFactoryFetcher([]);

        $from = Carbon::parse('2019-12-30 00:00:00')->toDateTimeString();
        $to = Carbon::parse('2019-12-31 00:00:00')->toDateTimeString();

        try {
            $response = $fetcher->setTimeSpan($from, $to)->capture();
            dump($response);
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
        $fetcher = new SlotFactoryFetcher([
            'username' => 'TT8E7D7040'
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
        $fetcher = new SlotFactoryFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SlotFactoryTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new SlotFactoryFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SlotFactoryTicket::class, 'replace'], $fetchTickets);

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