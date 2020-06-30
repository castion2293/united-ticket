<?php

use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\KkLotteryFetcher;
use SuperPlatform\UnitedTicket\Models\KkLotteryTicket;

class KkLotteryFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "bet_count" => "1",
        "bet_number" => "2",
        "bet_time" => "2020-01-06 15:47:17.0",
        "cancel_status" => "0",
        "cancel_time" => "",
        "create_time" => "2020-01-06 15:47:17",
        "estimate_win_price" => "18.0",
        "id" => "1214091049204383745",
        "ip" => "45.76.208.158",
        "issue_code" => "20200106018",
        "issue_seq" => "0",
        "issue_winning_code" => "7,9,2,2,7",
        "keyword" => "",
        "lottery_cnname" => "新疆時時彩",
        "lottery_enname" => "test9",
        "lottery_id" => "501",
        "method_code" => "1053697",
        "method_id" => "301",
        "method_name" => "定位胆十",
        "modes" => "0",
        "modify_time" => "2020-01-06 16:00:10",
        "multiple" => "1",
        "open_lottery_status" => "1",
        "orderBy" => "",
        "original_total_money" => "2.0",
        "platform_id" => "1212614899600965634",
        "rebate_status" => "0",
        "single_price" => "2.0",
        "source_type" => "1",
        "total_money" => "2.0",
        "user_bonus_group" => "1800",
        "user_id" => "1212678185285545985",
        "user_name" => "TEST2020010203",
        "user_rebate_rate" => "0.0",
        "win_price" => "18.0",
        "winning_status" => "1",
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
        $ticket = new KkLotteryTicket($this->rawTicketData_1);
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
        $fetcher = new KkLotteryFetcher([]);

        $from = '2020-01-06 00:00:00';
        $to = '2020-01-06 23:59:59';

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