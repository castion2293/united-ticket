<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\SuperFetcher;
use SuperPlatform\UnitedTicket\Models\SuperTicket;

class SuperFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "bet_gold" => "0",
        "m_id" => "TT8E7D7040",
        "up_no1" => "ALJJJJT51",
        "up_no2" => "ALJJJJT",
        "sn" => "200815514050864",
        "gameSN" => "577713116",
        "gsn" => "1939177",
        "m_date" => "2019-10-08 15:51:40",
        "count_date" => "2019-10-09",
        "team_no" => "1",
        "fashion" => "2",
        "league" => "MLB 美國職棒-季後賽-客隊總得分",
        "gold" => "100",
        "sum_gold" => "0.00",
        "result_gold" => "0.00",
        "main_team" => "休士頓太空人-小",
        "visit_team" => "休士頓太空人-大",
        "playing_score" => "0-0",
        "mv_set" => "1",
        "mode" => "",
        "chum_num" => "5+80",
        "compensate" => "0.930",
        "status" => "",
        "score1" => "",
        "score2" => "",
        "status_note" => "Y",
        "g_type" => "0",
        "matter" => "<span>休士頓太空人-大</span>&nbsp;<span class=t-c4>VS</span>&nbsp;<span class=t-c2>休士頓太空人-小</span>&nbsp;(主)<br><span class=t-c5>5+80</span>&nbsp;<span class=t-</span>",
        "end" => "0",
        "updated_msg" => "",
        "payout_time" => "0000-00-00 00:00:00",
        "now" => "1",
        "uuid" => "4f1f18ff-1f5b-3b23-8dc8-2772be304e99",
        "detail" => "null",
    ];

    protected $rawTicketData_2 = [
        "bet_gold" => "0",
        "m_id" => "TT8E7D7040",
        "up_no1" => "ALJJJJT51",
        "up_no2" => "ALJJJJT",
        "sn" => "200815513348240",
        "gameSN" => "563741477",
        "gsn" => "1938904",
        "m_date" => "2019-10-08 15:51:33",
        "count_date" => "2019-10-09",
        "team_no" => "1",
        "fashion" => "1",
        "league" => "MLB 美國職棒-季後賽(美聯)",
        "gold" => "100",
        "sum_gold" => "0.00",
        "result_gold" => "0.00",
        "main_team" => "坦帕灣光芒",
        "visit_team" => "休士頓太空人",
        "playing_score" => "0-0",
        "mv_set" => "1",
        "mode" => "2",
        "chum_num" => "2-5",
        "compensate" => "0.950",
        "status" => "",
        "score1" => "",
        "score2" => "",
        "status_note" => "Y",
        "g_type" => "0",
        "matter" => "<span>休士頓太空人</span>&nbsp;<span class=t-c5>2-5</span>&nbsp;<span class=t-c4>VS</span>&nbsp;<span class=t-c2>坦帕灣光芒</span>&nbsp;(主)<br><span class=t-c1>坦帕灣span>",
        "end" => "0",
        "updated_msg" => "",
        "payout_time" => "0000-00-00 00:00:00",
        "now" => "1",
        "uuid" => "6c44363a-2557-3b8c-8264-c4278a7c3ad3",
        "detail" => "null",
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
        $ticket = new SuperTicket($this->rawTicketData_1);
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
        //   Argitrange
        // -----------
        $username = 'TT8E7D7040';
        $hours = 24 * 14; // 14 天
        $dt = Carbon::now();
        $cp = $dt->copy();

        // -----------
        //   Act
        // -----------
        $fetcher = new SuperFetcher([
            'username' => $username
        ]);

        try {
            $response = $fetcher->setTimeSpan($cp->subHours($hours)->toDateTimeString(), $dt->toDateTimeString())->capture();
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
        $fetcher = new SuperFetcher([
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
        $fetcher = new SuperFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperTicket::class, 'replace'], $fetchTickets);

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
    public function testFetchSameFirstTicketWithDifferentEnd()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new SuperFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['end'] = '1';

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
    public function testFetchSameFirstTicketWithDifferentStatusNote()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new SuperFetcher([
            'username' => 'TT8E7D7040'
        ]);

        // 轉換狀態
        $this->rawTicketData_1['end'] = '1';

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['status_note'] = 'D';

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
        $fetcher = new SuperFetcher([
            'username' => 'TT8E7D7040'
        ]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([SuperTicket::class, 'replace'], $fetchTickets);

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