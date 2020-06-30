<?php


use SuperPlatform\UnitedTicket\Jobs\AllBetFetchEGameTicketJob;
use SuperPlatform\UnitedTicket\Jobs\AutoFetchTicketJob;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class AutoFetchTicketJobTest extends BaseTestCase
{
    protected $station;

    protected $user = [];

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
     * 測試呼叫「Sagaming 沙龍」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForSaGaming(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TTD2F4BC7D',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'sa_gaming'
        );
    }

    /**
     * 測試呼叫「AllBet 歐博」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForAllBet(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TTD2F4BC7D',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'all_bet'
        );
    }

    /**
     * 測試呼叫「AllBet 歐博」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForAllBetEGame(): void
    {
        $captureBegin = microtime();

        $from = '2019-12-05 00:00:00';
        $to = '2019-12-05 23:59:59';

        dispatch((new AllBetFetchEGameTicketJob('all_bet', $from, $to)));

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
     * 測試呼叫「Bingo 賓果」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForBingo(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TTAD90EFA4',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'bingo'
        );
    }

    /**
     * 測試呼叫「Super 體育」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForSuperSport(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TT461950AC',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'super_sport'
        );
    }

    /**
     * 測試呼叫「Maya 瑪雅」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForMaya(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TTAD90EFA4',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'maya'
        );
    }

    /**
     * 測試呼叫「Dream Game 夢遊」注單抓取器並轉換成整合注單格式的job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForDreamGame(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'dream_game'
        );
    }

    /**
     * 測試呼叫「AMEBA」注單抓取器並轉換成整合注單格式的job test
     */
    public function testAutoFetchTicketJobForAmeba(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'ameba'
        );
    }

    /**
     * 測試呼叫「9K彩球」注單抓取器並轉換成整合注單格式的job test
     */
    public function testAutoFetchTicketJobForNineKLottery(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'nine_k_lottery'
        );
    }

    /**
     * 測試呼叫「QT電子」注單抓取器並轉換成整合注單格式的job test
     */
    public function testAutoFetchTicketJobForQTech(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'q_tech'
        );
    }

    /**
     * 測試呼叫「人人棋牌」注單抓取器並轉換成整合注單格式的job test
     */
    public function testAutoFetchTicketJobForBoboPoker(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'bobo_poker'
        );
    }

    /**
     * 測試呼叫「SF電子」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForSlotFactory(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => 'TT8E7D7040',
                'user_id' => '01crs9ptg9226kn3sz505bxcta',
            ],
            'slot_factory'
        );
    }

    /**
     * 測試呼叫「S128鬥雞」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForCockFight(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'cock_fight'
        );
    }

    /**
     * 測試呼叫「CMD 體育」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForCmdSport(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'cmd_sport'
        );
    }

    /**
     * 測試呼叫「賓果牛牛」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForBingoBull(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'bingo_bull'
        );
    }

    /**
     * 測試呼叫「性感百家」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForAwcSexy(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'awc_sexy'
        );
    }

    /**
     * 測試呼叫「KK彩票」注單抓取器並轉換成整合注單格式job test
     *
     * @test
     */
    public function testAutoFetchTicketJobForKkLottery(): void
    {
        $this->testTicketUnitedIntegratorTransfer(
            [
                'username' => '',
                'user_id' => '',
            ],
            'kk_lottery'
        );
    }

    /**
     * @param array $aUserInfo
     * @param string $sStation
     */
    private function testTicketUnitedIntegratorTransfer(array $aUserInfo,
        string $sStation): void
    {
        $captureBegin = microtime();

        $this->user = $aUserInfo;

        dispatch((new AutoFetchTicketJob(
            $sStation,
            $this->user
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