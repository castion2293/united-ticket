<?php

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\BingoFetcher;
use SuperPlatform\UnitedTicket\Models\BingoTicket;

class BingoFetcherTest extends BaseTestCase
{
    /**
     * 測試當原生注單注入時，是否會為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTicketData = [
            'account' => 'dp391wujlph',
            'serial_no' => '63604217120286501',
            'bingo_no' => '107027197',
            'bet_suit' => 'super_big_small',
            'bet_type_group' => 'super_big',
            'numbers' => '',
            'bet' => '100.0000',
            'odds' => '1.957',
            'real_bet' => '100.0000',
            'real_rebate' => '0.0000',
            'bingo_type' => 'none',
            'bingo_odds' => '0.0000',
            'result' => 'none',
            'status' => 'system_rewarded',
            'win_lose' => '-100.0000',
            'remark' => '',
            'bet_at' => '2018-05-14 23:26:36',
            'adjust_at' => '2018-05-14 23:30:21',
            'root_serial_no' => '63604217120286501',
            'root_created_at' => '2018-05-14 23:26:36',
            'duplicated' => 0,
            'player' => [],
            'results' => [],
            'history' => []
        ];

        // -----------
        //   Act
        // -----------
        $ticket = new BingoTicket($rawTicketData);
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
        $username = 'TTAD90EFA4';
        $hours = 24 * 14; // 14 天
        $dt = Carbon::now();
        $cp = $dt->copy();

        // -----------
        //   Act
        // -----------
        $fetcher = new BingoFetcher([
            'username' => $username
        ]);
        try {
            $response = $fetcher->setTimeSpan($cp->subHours($hours)->toDateTimeString(), $dt->toDateTimeString())->capture($username);
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