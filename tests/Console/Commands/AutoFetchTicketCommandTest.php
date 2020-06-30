<?php


use Illuminate\Support\Facades\Artisan;

class AutoFetchTicketCommandTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

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
        "ancestor_ids" => ["root_id", "user_3_id", "user_6_id"],
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

    /**
     * 測試呼叫「Sagaming 沙龍」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForSaGaming(): void
    {
        $this->testTransferCommand(
            'sa_gaming',
            'TTD2F4BC7D',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「AllBet 歐博」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForAllBet(): void
    {
        $this->testTransferCommand(
            'all_bet',
            'TTD2F4BC7D',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「Bingo 賓果」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForBingo(): void
    {
        $this->testTransferCommand(
            'bingo',
            'TTAD90EFA4',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「Super 體育」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForSuperSport(): void
    {
        $this->testTransferCommand(
            'super_sport',
            'TT8E7D7040',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「Maya 瑪雅」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForMaya(): void
    {
        $this->testTransferCommand(
            'maya',
            'TTAD90EFA4',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「Dream Game 夢遊」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForDreamGame(): void
    {
        $this->testTransferCommand(
            'dream_game',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「AMEBA」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForAmrba(): void
    {
        $this->testTransferCommand(
            'ameba',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「9K彩球」自動注單抓取器並轉換成整合注單格式
     */
    public function testAutoFetchTicketCommandForNieKLottery(): void
    {
        $this->testTransferCommand(
            'nine_k_lottery',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「QT電子」自動注單抓取器並轉換成整合注單格式
     */
    public function testAutoFetchTicketCommandForQTech(): void
    {
        $this->testTransferCommand(
            'q_tech',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「WM真人」自動注單抓取器並轉換成整合注單格式
     */
    public function testAutoFetchTicketCommandForWMCasino(): void
    {
        $this->testTransferCommand(
            'wm_casino',
            'TTD2F4BC7D',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「人人棋牌」自動注單抓取器並轉換成整合注單格式
     */
    public function testAutoFetchTicketCommandForBoboPoker(): void
    {
        $this->testTransferCommand(
            'bobo_poker',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「AV 電子」自動注單抓取器並轉換成整合注單格式
     */
    public function testAutoFetchTicketCommandForForeverEight(): void
    {
        $this->testTransferCommand(
            'forever_eight',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「Slot Factory SF電子」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForSlotFactory(): void
    {
        $this->testTransferCommand(
            'slot_factory',
            'TT8E7D7040',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「Cock Fight S128鬥雞」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForCockFight(): void
    {
        $this->testTransferCommand(
            'cock_fight',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「CMD 體育」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForCmdSport(): void
    {
        $this->testTransferCommand(
            'cmd_sport',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「Bingo Bull 賓果牛牛」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForBingoBull(): void
    {
        $this->testTransferCommand(
            'bingo_bull',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「Awc Sexy 性感百家」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForAwcSexy(): void
    {
        $this->testTransferCommand(
            'awc_sexy',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「HB 電子」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForHabanero(): void
    {
        $this->testTransferCommand(
            'habanero',
            'TTD2F4BC7D',
            '01crs9ptg9226kn3sz505bxcta',
            null
        );
    }

    /**
     * 測試呼叫「KK Lottery KK彩票」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForKkLottery(): void
    {
        $this->testTransferCommand(
            'kk_lottery',
            '',
            '',
            null
        );
    }

    /**
     * 測試呼叫「Incorrect Score 反波膽」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAutoFetchTicketCommandForIncorrectScore(): void
    {
        $this->testTransferCommand(
            'incorrect_score',
            '',
            '',
            null
        );
    }

    private function testTransferCommand(string $sStation,
        string $sUsername,
        string $sUid,
        ?string $sPassword = '',
        ?callable $callback = null): void
    {
        $captureBegin = microtime();

        Artisan::call(
            'auto-fetch:ticket',
            [
                'station' => $sStation,
                'username' => $sUsername,
                'user_id' => $sUid,
                '--password' => $sPassword,
            ]
        );

        print_r(join(
            PHP_EOL,
            [
                '--',
                '　共花費 ' . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
                '=====================================',
                '',
            ]
        ));

        if ($callback !== null) $callback();
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