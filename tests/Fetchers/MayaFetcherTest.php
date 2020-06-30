<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\MayaFetcher;
use SuperPlatform\UnitedTicket\Models\MayaTicket;

class MayaFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "GameMemberID" => 1250159863,
        "BetNo" => "170500232934312",
        "BetMoney" => 20,
        "ValidBetMoney" => 20,
        "WinLoseMoney" => -20,
        "Handsel" => 0,
        "BetDetail" => "Zhuang",
        "State" => 2,
        "BetType" => 0,
        "BetDateTime" => "2019-10-05 11:19:45",
        "CountDateTime" => "2019-10-05 11:20:23",
        "AccountDateTime" => "2019-10-05 11:20:23",
        "Odds" => 0.95,
        "GameType" => "Baccarat",
        "Username" => "TT8E7D7040",
        "uuid" => "47c791ed-e7a5-3087-8f10-e756613fe421"
    ];

    protected $rawTicketData_2 = [
        "GameMemberID" => 1250159863,
        "BetNo" => "170500232934311",
        "BetMoney" => 10,
        "ValidBetMoney" => 10,
        "WinLoseMoney" => 10,
        "Handsel" => 0,
        "BetDetail" => "Xian",
        "State" => 2,
        "BetType" => 0,
        "BetDateTime" => "2019-10-05 11:18:55",
        "CountDateTime" => "2019-10-05 11:19:38",
        "AccountDateTime" => "2019-10-05 11:19:38",
        "Odds" => 1,
        "GameType" => "Baccarat",
        "Username" => "TT8E7D7040",
        "uuid" => "1371b659-5df2-3b0d-8532-79690e0fdf91"
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
        $ticket = new MayaTicket($this->rawTicketData_1);
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
        $fetcher = new MayaFetcher([
            'username' => $username
        ]);
        try {
            $response = $fetcher->setTimeSpan($cp->subHours($hours)->format('YmdHis'), $dt->format('YmdHis'))->capture($username);
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
        $username = 'TT8E7D7040';

        $fetcher = new MayaFetcher([
            'username' => $username
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
        $username = 'TT8E7D7040';

        $fetcher = new MayaFetcher([
            'username' => $username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([MayaTicket::class, 'replace'], $fetchTickets);

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
        $username = 'TT8E7D7040';

        $fetcher = new MayaFetcher([
            'username' => $username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([MayaTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['State'] = 4;

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
        $username = 'TT8E7D7040';

        $fetcher = new MayaFetcher([
            'username' => $username
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([MayaTicket::class, 'replace'], $fetchTickets);

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