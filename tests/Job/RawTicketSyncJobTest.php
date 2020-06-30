<?php

use Carbon\Carbon;
use SuperPlatform\UnitedTicket\Jobs\RawTicketSyncJob;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use SuperPlatform\UnitedTicket\Models\SuperLotteryRake;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class RawTicketSyncJobTest extends BaseTestCase
{
    protected $station;

    protected $user = [];

    protected $start;

    protected $end;

    // StationWallet 錢包假資料
    protected $stationWalletData = [
        "id" => "0000k1dvz89hyjpawspjhh5jt8",
        "user_id" => "Q1",
        "account" => "a61",
        "password" => "3cd24fb0d696",
        "station" => "all_bet",
        "status" => "active",
        "sync_status" => "free",
        "activated_status" => "no",
        "balance" => "0.0000",
        "remark" => "",
        "last_sync_at" => null,
        "created_at" => "2018-06-22 14:03:14",
        "updated_at" => "2018-06-22 14:03:14",
        "deleted_at" => null
    ];

    // NodeTree 假資料
    protected $nodeTreeData = [
        "id" => "Q1",
        "version" => 1,
        "temporal_start" => "2018-06-22 14:59:16",
        "temporal_end" => "2999-01-01 00:00:00",
        "parent_id" => "user_7_id",
        "ancestor_ids" => "root_id,user_3_id,user_6_id",
        "children_count" => 0,
        "children_ids" => "",
        "children_file_count" => 0,
        "children_file_ids" => "",
        "descendant_count" => 0,
        "descendant_ids" => "",
        "file_count" => 0,
        "file_ids" => "",
        "depth" => 4,
        "type" => "file",
        "created_at" => "2018-06-22 14:59:16",
        "updated_at" => "2018-06-22 14:59:16"
    ];

    // AllotTableData 假資料
    protected $allotTableData = [
        "root_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm1",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "all_bet",
            "game_scope" => "101",
            "allotment" => "0.9900",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ],

        "user_3_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm2",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "all_bet",
            "game_scope" => "101",
            "allotment" => "0.9800",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ],

        "user_6_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm3",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "all_bet",
            "game_scope" => "101",
            "allotment" => "0.9700",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ]
    ];

    // RebateTableData 假資料
    protected $rebateTableData = [
        "root_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm1",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "super_lottery",
            "game_scope" => "539",
            "rebate" => "0.3000",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ],

        "user_3_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm2",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "super_lottery",
            "game_scope" => "539",
            "rebate" => "0.2900",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ],

        "user_6_id" => [
            "id" => "0000k66ffe3rpevsce3kc00xm3",
            "version" => 1,
            "temporal_start" => "2018-06-22 15:26:35",
            "temporal_end" => "2999-01-01 00:00:00",
            "owner_id" => "01brh9q9amqp7mt7xqqb6b5k58",
            "game_station" => "super_lottery",
            "game_scope" => "539",
            "rebate" => "0.2800",
            "created_at" => "2018-06-22 15:26:35",
            "updated_at" => "2018-06-22 15:26:35",
        ]
    ];

    public function setUP()
    {
        //------------產生假資料，Unit Test測試時使用，整合測試先測UnitedIntegratorTransferCommandTest.php，再測這支-----------------------------------
        parent::setUp();

        $this->station = 'all_bet';
        $this->user = [
            'username' => '14a61',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];
        $this->start = now()->subDay(14)->toDateTimeString();
        $this->end = now()->toDateTimeString();

        // --- 描述 mock 的事件故事 ---
        // 在整個測試過程，建立一個StationWallet 類別物件
        // 並且被呼叫 getWalletByIdStation() 並傳入 $account, $station 參數
        // 而且回傳了 stationWalletData 內定義的假資料
        $this->mock = $this->initMock('SuperPlatform\StationWallet\StationWallet');
        $this->mock
            ->shouldReceive('getWalletByIdStation')
            ->andReturnUsing(function ($account, $station) {
                return $this->stationWalletData;
            });

        // --- 描述 mock 的事件故事 ---
        // 在整個測試過程，建立一個NodeTree 類別物件
        // 並且被呼叫 findNodeLatestVersion() 並傳入 $id 參數
        // 而且回傳了 nodeTreeData 內定義的假資料
        $this->mock = $this->initMock('SuperPlatform\NodeTree\NodeTree');
        $this->mock
            ->shouldReceive('findNodeByDateTime')
            ->andReturnUsing(function ($id) {
                return $this->nodeTreeData;
            });

        // --- 描述 mock 的事件故事 ---
        // 在整個測試過程，建立一個AllotTable 類別物件
        // 並且被呼叫 getAllotmentByIdStationScope() 並傳入 $id, $gameStation, $gameScope, $dateTime 參數
        // 而且回傳了 allotTableData 內定義的假資料
        $this->mock = $this->initMock('SuperPlatform\AllotTable\AllotTable');
        $this->mock
            ->shouldReceive('getAllotmentByIdStationScope')
            ->andReturnUsing(function ($id, $gameStation, $gameScope, $dateTime) {
                return array_get($this->allotTableData, $id);
            });

        // --- 描述 mock 的事件故事 ---
        // 在整個測試過程，建立一個RebateTable 類別物件
        // 並且被呼叫 getRebateByIdStationScope() 並傳入 $id, $gameStation, $gameScope, $dateTime 參數
        // 而且回傳了 rebateTableData 內定義的假資料
        $this->mock = $this->initMock('SuperPlatform\AllotTable\RebateTable');
        $this->mock
            ->shouldReceive('getRebateByIdStationScope')
            ->andReturnUsing(function ($id, $gameStation, $gameScope, $dateTime) {
                return array_get($this->rebateTableData, $id);
            });

        // --- 描述 mock 的事件故事 ---
        // 在整個測試過程，建立一個AllotTable 類別物件
        // 並且被呼叫 getAllotmentByIdStationScope() 並傳入 $id, $gameStation, $gameScope, $dateTime 參數
        // 而且回傳了 allotTableData 內定義的假資料
        $this->mock = $this->initMock('App\Models\Agent');
        $this->mock
            ->shouldReceive('find')
            ->andReturnUsing(function ($ancestor_id) {
                return (object)['username' => 'aaa'];
            });
    }

    /**
     * 測試呼叫「AllBet 歐博」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForAllBet()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => '14_a61',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];

        dispatch((new RawTicketSyncJob('all_bet', $this->user, now()->subDays(14)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Sagaming 沙龍」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForSaGaming()
    {
        $captureBegin = microtime();

        dispatch((new RawTicketSyncJob('sa_gaming', $this->user, now()->subDays(7)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Super 體育」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferSuperSport()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => 'a61',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];

        dispatch((new RawTicketSyncJob('super_sport', $this->user, now()->subDays(14)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Bingo 賓果」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForBingo()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => 'test11',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];

        dispatch((new RawTicketSyncJob('bingo', $this->user, now()->subDays(14)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Maya 瑪雅」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForMaya()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => 'test11',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];

        dispatch((new RawTicketSyncJob('maya', $this->user, now()->subDays(7)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Dream Game 夢遊」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForDreamGame()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => 'test11',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
        ];

        dispatch((new RawTicketSyncJob('dream_game', $this->user, now()->subDays(7)->toDateTimeString(), now()->toDateTimeString())));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Lottery 彩球」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferForSuperLottery()
    {
        $captureBegin = microtime();

        $this->user = [
            'username' => 'TT8E7D7040',
            'user_id' => '01d8n4wdss4acjnesrqby28xp6',
            'password' => 'TT9f1aacea'
        ];

        dispatch((new RawTicketSyncJob('super_lottery', $this->user, '2019-06-27 08:00:00', '2019-06-27 09:00:00')));

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
        $this->assertGreaterThanOrEqual(0, SuperLotteryRake::count());
    }

    /**
     * 測試呼叫「皇朝」注單抓取器並轉換成整合注單格式的job test
     */
//    public function testTicketUnitedIntegratorTransferForHongChow()
//    {
//        $this->testTicketUnitedIntegratorTransfer(
//            [
//                'username' => 'test11',
//                'user_id' => '01crs9ptg9226kn3sz505bxcta',
//            ],
//            'hong_chow');
//    }

    /**
     * 測試呼叫「AMEBA」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForAmeba(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'ts0987567876',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'ameba',
            Carbon::parse('2019-03-19 12:30:00')->toIso8601String(),
            Carbon::parse('2019-03-19 12:45:00')->toIso8601String()
        );
    }

    /**
     * 測試呼叫「手中寶」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForSoPower(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'ts0987567876',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'so_power',
            Carbon::parse('2019-03-18 20:00:00'),
            Carbon::parse('2019-03-18 21:00:00')
        );
    }

    /**
     * 測試呼叫「RTG」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForRealTimeGaming(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'ts0987567876',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'real_time_gaming',
            Carbon::parse('2019-04-09 09:30:00')->toIso8601String(),
            Carbon::parse('2019-04-09 21:45:00')->toIso8601String()
        );
    }

    /**
     * 測試呼叫「RG」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForRoyalGame(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'ts0987567876',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'royal_game',
            Carbon::parse('2019-05-21 20:30:00'),
            Carbon::parse('2019-05-21 21:45:00')
        );
    }

    /**
     * 測試呼叫「Ren Ni Ying」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForRenNiYing(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'ren_ni_ying',
            '2019-06-04 12:00:00',
            '2019-06-04 14:00:00'
        );
    }

    /**
     * 測試呼叫「Cq9」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForCq9(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'cq9_game',
            '2019-06-13 00:00:00',
            '2019-06-13 23:59:59'
        );
    }

    /**
     * 測試呼叫「9K彩球」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForNineKLottery(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'nine_k_lottery',
            '2019-06-19 14:00:00',
            '2019-06-19 16:00:00'
        );
    }

    /**
     * 測試呼叫「UFA 體育」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testTicketUnitedIntegratorTransferUfaSport()
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'ts0987567876',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'ufa_sport',
            Carbon::parse('2019-06-16 20:30:00'),
            Carbon::parse('2019-06-21 21:45:00')
        );
    }

    /**
     * 測試呼叫「WinnerSport」注單抓取器並轉換成整合注單格式的job test
     */
    public function testTicketUnitedIntegratorTransferForWinnerSport(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'winner_sport',
            '2019-07-01 17:00:00',
            '2019-07-03 18:00:00'
        );
    }

    /**
     * @param array $aUserInfo
     * @param string $sStation
     * @param string|null $sStartTime
     * @param string|null $sEndTime
     */
    private function testTicketUnitedIntegratorTransfer(array $aUserInfo,
                                                        string $sStation,
                                                        ?string $sStartTime = null,
                                                        ?string $sEndTime = null): void
    {
        $captureBegin = microtime();

        $this->user = $aUserInfo;

        dispatch((new RawTicketSyncJob(
            $sStation,
            $this->user,
            $sStartTime ?? now()->subDays(7)->toDateTimeString(),
            $sEndTime ?? now()->toDateTimeString()
        )));

        print_r(join(PHP_EOL, [
            '--',
            '　共花費 ' . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            '=====================================',
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, AllBetTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 輔助函式: 取得兩個時間的毫秒差
     *
     * @param $start
     * @param null $end
     * @return float
     */
    protected function microTimeDiff($start, $end = null)
    {
        if (!$end) {
            $end = microtime();
        }
        list($start_usec, $start_sec) = explode(" ", $start);
        list($end_usec, $end_sec) = explode(" ", $end);

        $diff_sec = intval($end_sec) - intval($start_sec);
        $diff_usec = floatval($end_usec) - floatval($start_usec);
        return floatval($diff_sec) + $diff_usec;
    }
}