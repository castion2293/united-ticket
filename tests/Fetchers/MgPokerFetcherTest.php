<?php

use SuperPlatform\UnitedTicket\Fetchers\MgPokerFetcher;
use SuperPlatform\UnitedTicket\Models\MgPokerTicket;

class MgPokerFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "gameId" => 104,
        "account" => "ddmg1PA857",
        "accountId" => 0,
        "platform" => "PC",
        "roundId" => "qafjet_11198_80x_1_1",
        "gameResult" => 1,
        "fieldId" => 10401,
        "filedName" => "新手房",
        "tableId" => 1,
        "chair" => 1,
        "bet" => 50,
        "validBet" => 50,
        "win" => 100,
        "lose" => 47.5,
        "fee" => 2.5,
        "enterMoney" => 450,
        "createTime" => "2020-05-16 23:9:28",
        "roundBeginTime" => "2020-05-16 23:8:53",
        "roundEndTime" => "2020-05-16 23:9:28",
        "ip" => "60.251.110.60",
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
        $ticket = new MgPokerTicket($this->rawTicketData_1);
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
        $fetcher = new MgPokerFetcher([]);

        try {
            
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2020-05-10 00:00:00',
                    '2020-05-25 00:00:00'
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

   
}