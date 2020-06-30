<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\WinnerSportTicket;

class WinnerSportFetcherTest extends BaseTestCase implements TestExample
{
    protected $rawTicketData_1 = [
        "id" => "BS00014832",
        "pr" => "0",
        "status" => "1",
        "stats" => "下注成功",
        "mark" => "",
        "meid" => "14676",
        "meusername" => "NDTT8E7D7040",
        "meusername1" => "TT8E7D7040",
        "gold" => "100",
        "gold_c" => "0.00",
        "io" => "0.890",
        "result" => "",
        "meresult" => "0.00",
        "gtype" => "BS",
        "rtype" => "OU",
        "g_title" => "美棒",
        "r_title" => "全場大小",
        "l_sname" => "美國MLB季後賽",
        "detail_1" => "",
        "orderdate" => "20191008",
        "IP" => "45.76.208.158 (JP)",
        "added_date" => "2019-10-08 17:32:40",
        "modified_date" => "2019-10-08 17:32:40",
        "detail" => "OAK 奧克蘭運動家[主] V.S BOS 波士頓紅襪  => 3+50 小 @ 0.890",
        "uuid" => "3da5266e-e946-32c2-848f-5bbab20b268a",
    ];

    protected $rawTicketData_2 = [
        "id" => "BS00014833",
        "pr" => "0",
        "status" => "1",
        "stats" => "下注成功",
        "mark" => "",
        "meid" => "14676",
        "meusername" => "NDTT8E7D7040",
        "meusername1" => "TT8E7D7040",
        "gold" => "100",
        "gold_c" => "0.00",
        "io" => "0.950",
        "result" => "",
        "meresult" => "0.00",
        "gtype" => "BS",
        "rtype" => "EOH1",
        "g_title" => "美棒",
        "r_title" => "上半單雙",
        "l_sname" => "美國MLB季後賽",
        "detail_1" => "",
        "orderdate" => "20191008",
        "IP" => "45.76.208.158 (JP)",
        "added_date" => "2019-10-08 17:32:48",
        "modified_date" => "2019-10-08 17:32:48",
        "detail" => "OAK 奧克蘭運動家[主] V.S BOS 波士頓紅襪  => 雙 @ 0.950",
        "uuid" => "e3ff5e5e-7d7a-37cf-9a30-d6a5da4c2c55",
    ];

    /**
     * 測試產生的 uuid 主鍵是否正確
     */
    public function testRawTicketUuid(): void
    {
        $oRawTicketModel = new WinnerSportTicket($this->rawTicketData_1);
        $aTicket = $oRawTicketModel->toArray();
        $aTicket['uuid'] = $oRawTicketModel->uuid;

        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $oRawTicketModel->uuid);

        $this->assertArrayHasKey('uuid', $aTicket);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * 測試注單抓取器 capture 方法是否正確
     */
    public function testSuccessCapture(): void
    {
        $oFetcher = new WinnerSportFetcher();

        try {
            $aTicketsMerged = $oFetcher->setTimeSpan('2019-07-01 17:00:00', '2019-07-03 18:00:00')->capture();

            $this->assertArrayHasKey('tickets', $aTicketsMerged);
        } catch (ApiCallerException $e) {
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $e);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $e->getMessage());
            $this->assertEquals(true, is_array($e->response()));
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
        $fetcher = new WinnerSportFetcher();

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
        $fetcher = new WinnerSportFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WinnerSportTicket::class, 'replace'], $fetchTickets);

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
    public function testFetchSameFirstTicketWithDifferentResult()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new WinnerSportFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WinnerSportTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['result'] = 'W';

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
        $fetcher = new WinnerSportFetcher();

        // 轉換狀態
        $this->rawTicketData_1['result'] = 'W';

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WinnerSportTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['status'] = '3';

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
        $fetcher = new WinnerSportFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([WinnerSportTicket::class, 'replace'], $fetchTickets);

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