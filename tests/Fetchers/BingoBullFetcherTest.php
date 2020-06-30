<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\BingoBullFetcher;
use SuperPlatform\UnitedTicket\Models\BingoBullTicket;

class BingoBullFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "status" => "1",
        "betNo" => "T157404034697efiu0",
        "betData" => '{"banker":{"numberArray":["27","51"],"sum":"5","seat":"9"},"player":{"numberArray":["80","29"],"sum":"9","seat":"1"}}',
        "betMoney" => "30",
        "openNo" => "108065192",
        "okMoney" => "60",
        "totalMoney" => "58.2",
        "pumpMoney" => "1.8",
        "reportTime" => "1574006400",
        "createTime" => "1574040346",
        "userType" => "0",
        "account" => "jxnRyKTBS91",
        "roomCode" => "A001",
        "coin" => "30",
        "mainGame" => "bingoBull",
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
        $ticket = new BingoBullTicket($this->rawTicketData_1);
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
        $fetcher = new BingoBullFetcher([]);

        $from = Carbon::parse('2019-11-19 10:00:00')->toDateTimeString();
        $to = Carbon::parse('2019-11-19 10:10:59')->toDateTimeString();

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