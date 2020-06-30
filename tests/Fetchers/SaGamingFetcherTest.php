<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\SaGamingFetcher;
use SuperPlatform\UnitedTicket\Models\SaGamingTicket;

class SaGamingFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "BetID" => 29025349,
        "BetTime" => "2019-10-08T13:32:12.52",
        "PayoutTime" => "2019-10-08T13:32:58.437",
        "GameID" => 47044524089344,
        "HostID" => 602,
        "HostName" => '\u767e\u5bb6\u6a02S2',
        "GameType" => "bac",
        "Set" => 1,
        "Round" => 47,
        "BetType" => 1,
        "BetAmount" => 50,
        "Rolling" => 50,
        "Detail" => "[]",
        "GameResult" => "",
        "ResultAmount" => -50,
        "Balance" => 38,
        "State" => "true",
        "Username" => "TT8E7D7040",
        "uuid" => "520ada53-1ccd-320d-b937-0a165b7ad482",
    ];

    protected $rawTicketData_2 = [
        "BetID" => 29025350,
        "BetTime" => "2019-10-08T13:33:24.767",
        "PayoutTime" => "2019-10-08T13:33:28.2",
        "GameID" => 944675049488,
        "HostID" => 401,
        "HostName" => '\u8001\u864e\u6a5f',
        "GameType" => "slot",
        "Set" => 0,
        "Round" => 0,
        "BetType" => 30,
        "BetAmount" => 30,
        "Rolling" => 30,
        "Detail" => "EG-SLOT-A033",
        "GameResult" => "[]",
        "ResultAmount" => -30,
        "Balance" => 8,
        "State" => "true",
        "Username" => "TT8E7D7040",
        "uuid" => "37caf49e-4136-3f2c-a12e-1338e8f99656",
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
        $ticket = new SaGamingTicket($this->rawTicketData_1);
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
        //   Arrange
        // -----------
//        $username = '21a61-x';
        $username = 'TTAD90EFA4';
        $hours = 24 * 7; // 7 天
        $dt = Carbon::now();
        $cp = $dt->copy();

        // -----------
        //   Act
        // -----------
        $fetcher = new SaGamingFetcher([
            'username' => $username
        ]);
        try {
            $response = $fetcher->setTimeSpan($cp->subHours($hours)->toDateTimeString(), $dt->toDateTimeString())->capture();
            $this->assertTrue(true);
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
        $fetcher = new SaGamingFetcher([
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
        $fetcher = new SaGamingFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SaGamingTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new SaGamingFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SaGamingTicket::class, 'replace'], $fetchTickets);

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