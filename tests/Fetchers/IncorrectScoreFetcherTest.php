<?php


namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Carbon\Carbon;
use SuperPlatform\UnitedTicket\Models\IncorrectScoreTicket;

class IncorrectScoreFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
      "ticketNo" => 21357140,
      "user" => "testsup_a1b2c3d4",
      "sportType" => 1,
      "orderTime" => "2020/02/17 11:16:08",
      "betTime" => "2020/02/17 10:38:47",
      "betamount" => 100.0,
      "validBetAmount" => 95.0,
      "currency" => "RMB",
      "winlose" => 3.9,
      "isFinished" => 1,
      "statusType" => "Y",
      "wagerGrpId" => 10,
      "betIp" => "125.227.218.181",
      "cType" => "WA",
      "device" => "M",
      "accdate" => "2020/02/18 00:00:00",
      "acctId" => 1,
      "refNo" => 22845404,
      "league" => "阿根廷甲组联赛",
      "match" => "独立 vs 沙兰迪兵工厂",
      "betOption" => 3,
      "hdp" => 0,
      "odds" => 4.11,
      "oddsDesc" => "%",
      "winlostTime" => "2020/02/17 11:16:08",
      "scheduleTime" => "2020/02/18 08:10:00",
      "ftScore" => "2:4",
      "curScore" => "0:0",
      "wagerTypeID" => 120,
      "cutline" => "2 - 2",
      "odddesc" => "香港盘",
      "uuid" => "4f1f18ff-1f5b-3b23-8dc8-2772be304e99",
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
        $ticket = new IncorrectScoreTicket($this->rawTicketData_1);
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
        $fetcher = new IncorrectScoreFetcher([]);

        $from = '2020-05-13 00:00:00';
        $to = '2020-05-30 23:59:59';

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