<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\AwcSexyFetcher;
use SuperPlatform\UnitedTicket\Models\AwcSexyTicket;

class AwcSexyFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "gameType" => "LIVE",
        "comm" => 0,
        "txTime" => "2019-12-24T09:41:08+08:00",
        "bizDate" => "2019-12-24T00:00:00+08:00",
        "winAmt" => 19.5,
        "gameInfo" => '{"roundStartTime":"12/24/2019 09:40:55.909","winner":"BANKER","ip":"45.76.208.158","odds":0.95,"tableId":1,"dealerDomain":"Mexico","winLoss":9.5,"status":"WIN"}',
        "betAmt" => 10,
        "updateTime" => "2019-12-24T09:41:45+08:00",
        "jackpotWinAmt" => 0,
        "turnOver" => 9.5,
        "userId" => "test2019122001",
        "betType" => "Banker",
        "platform" => "SEXYBCRT",
        "txStatus" => 1,
        "jackpotBetAmt" => 0,
        "createTime" => "2019-12-24T09:41:08+08:00",
        "platformTxId" => "BAC-58824188",
        "realBetAmt" => 10,
        "gameCode" => "MX-LIVE-001",
        "currency" => "VND",
        "ID" => 3940571,
        "realWinAmt" => 19.5,
        "roundId" => "Mexico-01-GA238100020",
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
        $ticket = new AwcSexyTicket($this->rawTicketData_1);
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
        $fetcher = new AwcSexyFetcher([]);

        $from = Carbon::parse('2019-12-24 09:00:00')->toIso8601String();
        $to = Carbon::parse('2019-12-24 10:00:00')->toIso8601String();

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
}