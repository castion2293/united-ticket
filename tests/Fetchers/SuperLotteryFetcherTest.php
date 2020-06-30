<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\SuperLotteryFetcher;
use SuperPlatform\UnitedTicket\Models\SuperLotteryRake;
use SuperPlatform\UnitedTicket\Models\SuperLotteryTicket;

class SuperLotteryFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "state" => 0,
        "name" => "A19033",
        "lottery" => "",
        "bet_no" => "1",
        "bet_time" => "2019-10-14 10:51:44",
        "account" => "ws14@TT8E7D7040",
        "game_id" => 11,
        "game_type" => "102",
        "bet_type" => "51",
        "detail" => "13",
        "cmount" => "9",
        "gold" => "0",
        "odds" => "36",
        "retake" => "0.9",
        "status" => 0,
        "uuid" => "7714764c-4a50-3803-b88d-1542c33e6426",
    ];

    protected $rawTicketData_2 = [
        "state" => 0,
        "name" => "B108022",
        "lottery" => "",
        "bet_no" => "1",
        "bet_time" => "2019-10-14 10:51:58",
        "account" => "ws14@TT8E7D7040",
        "game_id" => 12,
        "game_type" => "102",
        "bet_type" => "51",
        "detail" => "24",
        "cmount" => "8",
        "gold" => "0",
        "odds" => "36",
        "retake" => "0.8",
        "status" => 0,
        "uuid" => "814ffad1-89f3-30b8-98b2-ad14f167b591",
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
        $ticket = new SuperLotteryTicket($this->rawTicketData_1);
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
        //   Arrange 帶入上層帳號
        // -----------
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        // -----------
        //   Act
        // -----------
        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        try {
            $response = $fetcher->setTimeSpan('2019-05-13 08:00:00', '2019-05-13 08:00:00')->capture();
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
     * 測試當退水總帳，是否會為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testSuperLotteryRakeUuid()
    {
        // -----------
        //   Arrange
        // -----------
        $superLotteryRake = [
            'level' => '1',
            'user_identify' => '01d8n4wdss4acjnesrqby28xp6',
            'account' => 'TT8E7D7040',
            'bet_date' => '2019-06-27',
            'game_scope' => 'liu_he',
            'category' => 'keno',
            'ccount' => 1,
            'cmount' => 100.00,
            'bmount' => 100.00,
            'm_gold' => 0.00,
            'm_rake' => 10.0000,
            'm_result' => -90.00,
            'up_no1_result' => -90,
            'up_no2_result' => -78,
            'up_no1_rake' => -12,
            'up_no2_rake' => -12.0000,
            'up_no1' => 'ALJJJKEltex5',
            'up_no2' => 'ALJJJKE',
        ];

        // -----------
        //   Act
        // -----------
        $ticket = new SuperLotteryRake($superLotteryRake);
        $datas = $ticket->toArray();
        $datas['uuid']=$ticket->uuid;

        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
        $this->assertEquals(1, $isUUID3);
        $this->assertArrayHasKey('uuid', $datas);
    }

    /**
     * 測試呼叫rake()的動作成功，並取得成功的回應結果的案例
     *
     * @test
     */
    public function testSuccessRake()
    {
        // -----------
        //   Arrange 帶入上層帳號
        // -----------
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';
        $userIdentify = '01d8n4wdss4acjnesrqby28xp6';

        // -----------
        //   Act
        // -----------
        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        try {
            $response = $fetcher->setRakeDate('2019-06-26 08:00:00', '2019-06-27 08:00:00')->rakeCapture();
            $this->assertArrayHasKey('rakes', $response);
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
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
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
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperLotteryTicket::class, 'replace'], $fetchTickets);

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
    public function testFetchSameFirstTicketWithDifferentStatus()
    {
        // -----------
        //   Arrange
        // -----------
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperLotteryTicket::class, 'replace'], $fetchTickets);

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
     * 測試撈同樣第一張，然後比對會找出不一樣的結果，所以會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicketWithDifferentState()
    {
        // -----------
        //   Arrange
        // -----------
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperLotteryTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['state'] = 1;

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
        $account = 'TT8E7D7040';
        $passwd = 'TT9f1aacea';

        $fetcher = new SuperLotteryFetcher([
            'username' => $account,
            'password' => $passwd
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperLotteryTicket::class, 'replace'], $fetchTickets);

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