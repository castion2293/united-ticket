<?php

use Carbon\Carbon;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use Illuminate\Support\Facades\Artisan;
use SuperPlatform\UnitedTicket\Models\SaGamingTicket;
use SuperPlatform\UnitedTicket\Models\SuperLotteryRake;
use SuperPlatform\UnitedTicket\Models\SuperLotteryTicket;
use SuperPlatform\UnitedTicket\Models\SuperTicket;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use SuperPlatform\UnitedTicket\Models\WmCasinoTicket;

class UnitedIntegratorTransferCommandTest extends BaseTestCase
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
     * 測試呼叫「AllBet 歐博」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForAllBet()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'all_bet',
            'username' => 'TTD2F4BC7D',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDay(14)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

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
     * 測試呼叫「AllBet 歐博電子注單」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testAllBetEGameFetchTicketCommandForAllBet()
    {
        $captureBegin = microtime();

        Artisan::call('all_bet:ticket-fetch', [
            '--startTime' => '2019-12-05 00:00:00',
            '--endTime' => '2019-12-05 23:59:59',
        ]);

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
     * 測試呼叫「Sagaming 沙龍」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForSaGaming()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'sa_gaming',
            'username' => '`TTD2F4BC7D',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDays(7)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, SaGamingTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Super 體育」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForSuper()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'super_sport',
            'username' => 'TT461950AC',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDay(300)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, SuperTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「Bingo 賓果」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForBingo()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'bingo',
            'username' => 'TTAD90EFA4',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDays(7)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));
    }

    /**
     * 測試呼叫「Maya 瑪雅」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForMaya()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'maya',
            'username' => 'TTAD90EFA4',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDay(7)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));
    }

    /**
     * 測試呼叫「Dream Game 夢遊」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForDreamGame()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'dream_game',
            'username' => '',
            'user_id' => '',
            '--startTime' => '2019-11-14 00:00:00',
            '--endTime' => '2019-11-14 23:59:59',
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));
    }

    /**
     * 測試呼叫「Lottery 彩球」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForSuperLottery()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'super_lottery',
            'username' => 'TT8E7D7040',
            'user_id' => '01d8n4wdss4acjnesrqby28xp6',
            '--password' => 'TT9f1aacea',
            '--startTime' => '2019-06-27 08:00:00',
            '--endTime' => '2019-06-27 09:00:00',
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, SuperLotteryTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
        $this->assertGreaterThanOrEqual(0, SuperLotteryRake::count());
    }

    /**
     * 測試呼叫「皇朝」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForHongChow()
    {
        $this->testTransferCommand(
            'hong_chow',
            'ts0987567876',
            '01crs9ptg9226kn3sz505bxcta'
        );
    }

    /**
     * 測試呼叫「AMEBA」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForAmeba(): void
    {
        $this->testTransferCommand(
            'ameba',
            'ts0987567876',
            '01crs9ptg9226kn3sz505bxcta',
            null,
            Carbon::parse('2019-03-19 12:30:00')->toIso8601String(),
            Carbon::parse('2019-03-19 12:45:00')->toIso8601String()
        );
    }

    /**
     * 測試呼叫「RTG」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForRealTimeGaming(): void
    {
        $this->testTransferCommand(
            'real_time_gaming',
            'ts0987567876',
            '01crs9ptg9226kn3sz505bxcta',
            null,
            Carbon::parse('2019-04-10 12:30:00')->toIso8601String(),
            Carbon::parse('2019-04-11 21:45:00')->toIso8601String()
        );
    }

    /**
     * 測試呼叫「手中寶」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForSoPower(): void
    {
        $this->testTransferCommand(
            'so_power',
            'ts0987567876',
            '01crs9ptg9226kn3sz505bxcta',
            null,
            Carbon::parse("2019-03-18 20:00:00"),
            Carbon::parse("2019-03-18 21:00:00")
        );
    }

    /**
     * 測試呼叫「Ren Ni Ying」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForRenNiYing(): void
    {
        $this->testTransferCommand(
            'ren_ni_ying',
            '',
            '',
            null,
            '',
            ''
        );

        $this->assertGreaterThanOrEqual(0, SuperTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「cq9」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForCq9(): void
    {
        $this->testTransferCommand(
            'cq9_game',
            '',
            '',
            null,
            Carbon::parse("2019-09-10 00:00:00"),
            Carbon::parse("2019-09-10 23:59:59")
        );
    }

    /**
     * 測試呼叫「9K彩球」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForNineKLottery(): void
    {
        $this->testTransferCommand(
            'nine_k_lottery',
            '',
            '',
            null,
            Carbon::parse("2019-09-05 00:00:00"),
            Carbon::parse("2019-09-05 23:00:00")
        );
    }

    /**
     * 測試呼叫「UFA」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForUFA(): void
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();

        Artisan::call('ticket-integrator:transfer', [
            'station' => 'ufa_sport',
            'username' => 'TTAD90EFA4',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDay(7)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));
    }

    /**
     * 測試呼叫「WinnerSport」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForWinnerSport(): void
    {
        $this->testTransferCommand(
            'winner_sport',
            '',
            '',
            null,
            '2019-07-01 17:00:00',
            '2019-07-03 18:00:00'
        );
    }

    /**
     * 測試呼叫「QTech」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForQTech(): void
    {
        $this->testTransferCommand(
            'q_tech',
            '',
            '',
            null,
            '2019-08-28 00:00:00',
            '2019-08-28 23:59:59'
        );
    }

    /**
     * 測試呼叫「WM 真人」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForWMCasino()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $cp = $dt->copy();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'wm_casino',
            'username' => '`TTD2F4BC7D',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $cp->subDays(1)->toDateTimeString(),
            '--endTime' => $dt->toDateTimeString(),
        ]);

        print_r(join(PHP_EOL, [
            "--",
            "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
            "=====================================",
            '',
        ]));

        $this->assertGreaterThanOrEqual(0, WmCasinoTicket::count());
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());
    }

    /**
     * 測試呼叫「人人棋盤」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForBoboPoker(): void
    {
        $this->testTransferCommand(
            'bobo_poker',
            '',
            '',
            null,
            '2019-09-19 17:00:00',
            '2019-09-19 17:59:59'
        );
    }

    /**
     * 測試呼叫「AV 電子」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForForeverEight(): void
    {
        $this->testTransferCommand(
            'forever_eight',
            '',
            '',
            null,
            '2019-10-10 17:00:00',
            '2019-10-19 17:59:59'
        );
    }

    /**
     * 測試呼叫「Slot Factory SF電子」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForSlotFactory()
    {
        $captureBegin = microtime();

        $dt = Carbon::now();
        $from = Carbon::parse('2019-12-30 00:00:00')->toDateTimeString();
        $to = Carbon::parse('2019-12-31 00:00:00')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'slot_factory',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「Cock Fight S128鬥雞」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForCockFight()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2019-11-13 09:30:00')->toDateTimeString();
        $to = Carbon::parse('2019-11-13 10:00:00')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'cock_fight',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「CMD 體育」注單抓取器並轉換成整合注單格式
     */
    public function testUnitedIntegratorTransferCommandForCmdSport(): void
    {
        $this->testTransferCommand(
            'cmd_sport',
            '',
            '',
            null,
            '2019-11-20 09:00:00',
            '2019-11-26 23:59:59'
        );
    }

    /**
     * 測試呼叫「Bingo Bull 賓果牛牛」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForBingoBull()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2019-11-19 09:00:00')->toDateTimeString();
        $to = Carbon::parse('2019-11-19 23:59:59')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'bingo_bull',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「Awc Sexy 性感百家」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForAwcSexy()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2019-12-24 09:00:00')->toDateTimeString();
        $to = Carbon::parse('2019-12-24 10:00:00')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'awc_sexy',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「HB 電子」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForHabanero()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2020-01-06 09:00:00')->toDateTimeString();
        $to = Carbon::parse('2020-01-31 10:00:00')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'habanero',
            'username' => 'TTAD90EFA4',
            'user_id' => '01crs9ptg9226kn3sz505bxcta',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「KK Lottery KK彩票」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForKkLottery()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2020-01-08 00:00:00')->toDateTimeString();
        $to = Carbon::parse('2020-01-08 23:59:59')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'kk_lottery',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * 測試呼叫「Incorrect Score 反波膽」注單抓取器並轉換成整合注單格式
     *
     * @test
     */
    public function testUnitedIntegratorTransferCommandForIncorrectScore()
    {
        $captureBegin = microtime();

        $from = Carbon::parse('2020-02-13 00:00:00')->toDateTimeString();
        $to = Carbon::parse('2020-03-08 23:59:59')->toDateTimeString();
        Artisan::call('ticket-integrator:transfer', [
            'station' => 'incorrect_score',
            'username' => '',
            'user_id' => '',
            '--startTime' => $from,
            '--endTime' => $to,
        ]);

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
     * @param string $sStation
     * @param string $sUsername
     * @param string $sUid
     * @param string|null $sPassword
     * @param string|null $sStartTime
     * @param string|null $sEndTime
     * @param callable|null $callback
     */
    private function testTransferCommand(string $sStation,
                                         string $sUsername,
                                         string $sUid,
                                         ?string $sPassword = '',
                                         ?string $sStartTime = '',
                                         ?string $sEndTime = '',
                                         ?callable $callback = null): void
    {
        $captureBegin = microtime();

        Artisan::call(
            'ticket-integrator:transfer',
            [
                'station' => $sStation,
                'username' => $sUsername,
                'user_id' => $sUid,
                '--password' => $sPassword,
                '--startTime' => $sStartTime,
                '--endTime' => $sEndTime,
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