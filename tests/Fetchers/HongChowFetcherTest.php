<?php
//
//use SuperPlatform\UnitedTicket\Fetchers\HongChowFetcher;
//use SuperPlatform\UnitedTicket\Models\HongChowTicket;
//
//class HongChowFetcherTest extends BaseTestCase
//{
//    /**
//     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
//     */
//    public function testRawTicketUuid()
//    {
//        // -----------
//        //   Arrange
//        // -----------
//        $rawTicketData = [
//            'bet_id' => 5574,
//            'user_name' => 'upgaming@upgaming',
//            'bettype' => 0,
//            'betamount' => 10,
//            'refundamount' => '',
//            'returnamount' => '',
//            'bettime' => '2019-01-29 14:35:04',
//            'betip' => '139.162.37.106',
//            'betsrc' => 1,
//            'part_id' => 1,
//            'part_name' => '[a]',
//            'part_odds' => 5550,
//            'game_id' => 1,
//            'game_name' => 'LOL',
//            'match_name' => '2018全明星赛',
//            'race_name' => '正赛',
//            'han_id' => 2446,
//            'han_name' => '比赛获胜方',
//            'team1_name' => 'RNG',
//            'team2_name' => 'FW',
//            'round' => 1,
//            'result' => '',
//            'reckondate' => '',
//            'status2' => 54,
//            'sync_version' => 2,
//        ];
//
//        // -----------
//        //   Act
//        // -----------
//        $ticket = new HongChowTicket($rawTicketData);
//        $datas = $ticket->toArray();
//        $datas['uuid'] = $ticket->uuid;
//
//        // -----------
//        //   Assert
//        // -----------
//        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
//        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
//
//        $this->assertArrayHasKey('uuid', $datas);
//        $this->assertEquals(1, $isUUID3);
//    }
//
//    /**
//     * @throws Exception
//     */
//    public function testSuccessCapture()
//    {
//        // -----------
//        //   Act
//        // -----------
//        $fetcher = new HongChowFetcher([]);
//
//        try {
//            $response = $fetcher->capture();
//            $this->assertArrayHasKey('tickets', $response);
//        } catch (\SuperPlatform\ApiCaller\Exceptions\ApiCallerException $exc) {
//            // -----------
//            //   Assert
//            // -----------
//            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
//            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $exc->getMessage());
//            $this->assertEquals(true, is_array($exc->response()));
//        }
//    }
//}