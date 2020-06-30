<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\CockFightFetcher;
use SuperPlatform\UnitedTicket\Models\CockFightTicket;

class CockFightFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        'ticket_id' => 7,
        'login_id' => 'TEST20191108',
        'arena_code' => 'AR1',
        'arena_name_cn' => '安蒂波洛',
        'match_no' => 'M2388',
        'match_type' => 'TOURNAMENT',
        'match_date' => '2019-11-11',
        'fight_no' => 18,
        'fight_datetime' => '2019-11-11 11:10:57',
        'meron_cock' => 'FEB 4 COCK SA PAMP',
        'meron_cock_cn' => '龍',
        'wala_cock' => 'AZA 93',
        'wala_cock_cn' => '鳯',
        'bet_on' => 'BDD',
        'odds_type' => 'HK',
        'odds_asked' => 8.00,
        'odds_given' => 8.00,
        'stake' => 100,
        'stake_money' => 100.0000,
        'balance_open' => 111.1200,
        'balance_close' => 11.1200,
        'created_datetime' => '2019-11-11 11:15:42',
        'fight_result' => 'WALA',
        'status' => 'LOSE',
        'winloss' => -100.0000,
        'comm_earned' => 0.0000,
        'payout' => 0.0000,
        'balance_open1' => 11.1200,
        'balance_close1' => 11.1200,
        'processed_datetime' => '2019-11-11 11:18:49'
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
        $ticket = new CockFightTicket($this->rawTicketData_1);
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
        $fetcher = new CockFightFetcher([]);

        $from = Carbon::parse('2019-11-13 09:30:00')->toDateTimeString();
        $to = Carbon::parse('2019-11-13 10:00:00')->toDateTimeString();

        try {
            $response = $fetcher->setTimeSpan($from, $to)->capture();
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