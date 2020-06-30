<?php

use SuperPlatform\UnitedTicket\Fetchers\WmCasinoFetcher;
use SuperPlatform\UnitedTicket\Models\WmCasinoTicket;

class WmCasinoFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "user" => "TT8E7D7040",
        "betId" => "29231851",
        "betTime" => "2019-10-09 11:02:38",
        "beforeCash" => "1010.0000",
        "bet" => "2.0000",
        "validbet" => "2.0000",
        "water" => "0.0000",
        "result" => "-2.0000",
        "betResult" => "闲",
        "waterbet" => "2.0000",
        "winLoss" => "-2.0000",
        "ip" => "45.76.208.158",
        "gid" => "101",
        "event" => "112221640",
        "eventChild" => "17",
        "round" => "112221640",
        "subround" => "17",
        "tableId" => "1",
        "commission" => "0",
        "settime" => "2019-10-09 11:03:06",
        "reset" => "N",
        "gameResult" => "庄:♦6♠Q 闲:♠Q♠2♣2",
        "gname" => "百家乐",
        "uuid" => "f4fc5e47-b579-34f4-bbc7-70afdf372047",
    ];

    protected $rawTicketData_2 = [
        "user" => "TT8E7D7040",
        "betId" => "29231854",
        "betTime" => "2019-10-09 11:03:23",
        "beforeCash" => "1008.0000",
        "bet" => "2.0000",
        "validbet" => "0.0000",
        "water" => "0.0000",
        "result" => "0.0000",
        "betResult" => "庄",
        "waterbet" => "0.0000",
        "winLoss" => "0.0000",
        "ip" => "45.76.208.158",
        "gid" => "101",
        "event" => "112221640",
        "eventChild" => "18",
        "round" => "112221640",
        "subround" => "18",
        "tableId" => "1",
        "commission" => "0",
        "settime" => "2019-10-09 11:03:59",
        "reset" => "N",
        "gameResult" => "庄:♠J♠2♣4 闲:♣A♥J♣5",
        "gname" => "百家乐",
        "uuid" => "3a7425b5-a44c-3404-8452-3bb044abab08",
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
        $ticket = new WmCasinoTicket($this->rawTicketData_1);
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
        $fetcher = new WmCasinoFetcher([]);

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
        $fetcher = new WmCasinoFetcher([]);

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
        $fetcher = new WmCasinoFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WmCasinoTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new WmCasinoFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WmCasinoTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['reset'] = 'Y';

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
        $fetcher = new WmCasinoFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WmCasinoTicket::class, 'replace'], $fetchTickets);

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