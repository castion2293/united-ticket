<?php

use SuperPlatform\UnitedTicket\Converters\MgPokerConverter;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class MgPokerConvertTest extends BaseTestCase
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
    // 在整個測試過程，建立一個Agent 類別物件
    // 並且被呼叫 find() 並傳入 $ancestor_id 參數
    // 而且回傳了 假的代理帳號 
    $this->mock = $this->initMock('App\Models\Agent');
    $this->mock
        ->shouldReceive('find')
        ->andReturnUsing(function ($ancestor_id) {
            // 假的代理帳號
            return (object)['username' => 'aaa'];
        });
}
    private $rawTickets = [
        'tickets' => [
            [
                "gameId" => 103,
                "account" => "a1b2c3d4",
                "accountId" => 0,
                "platform" => "App",
                "roundId" => "qapof2_121106_7y5_1001_5",
                "fieldId" => 10301,
                "filedName" => "新手房",
                "tableId" => 1001,
                "chair" => 5,
                "bet" => 500,
                "validBet" => 500,
                "win" => 0,
                "lose" => -500,
                "fee" => 0,
                "enterMoney" => 6907.66,
                "createTime" => "2020-05-22 10:33:19",
                "roundBeginTime" => "2020-05-22 10:33:2",
                "roundEndTime" => "2020-05-22 10:33:18",
                "ip" => "45.76.208.158",
                "uuid" => "a72fbd32-4d35-315e-b2e9-7753cd8137fd",
            ],
            [
                "gameId" => 104,
                "account" => "ddmg1PA857",
                "accountId" => 0,
                "platform" => "PC",
                "roundId" => "qafjet_11198_80x_1_1",
                "fieldId" => 10401,
                "filedName" => "新手房",
                "tableId" => 1,
                "chair" => 1,
                "bet" => 50,
                "validBet" => 50,
                "win" => 100,
                "lose" => 47.5,
                "fee" => 2.5,
                "enterMoney" => 450,
                "createTime" => "2020-05-16 23:9:28",
                "roundBeginTime" => "2020-05-16 23:8:53",
                "roundEndTime" => "2020-05-16 23:9:28",
                "ip" => "60.251.110.60",
                "uuid" => "475a33b0-8d98-3bb5-bd1a-4c3f45880510",
            ],
        ],
    ];
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

    // Agent 假資料
    // protected $agent = [
    //     "id" => "01e8xkqt3829syd4d7x4ehc0q4",
    //     "username" => "DFL3",
    //     "password" => "$2y$10$8Uqb4kngcSnYOVZ4e4ItzeQR6YrYQ25nqij3ZlH/yA7oLo7QWDTAm",
    //     "co_account_prefix" => "usq",
    //     "name" => "系統測試 DFL3",
    //     "lastname" => "lDFL3",
    //     "firstname" => "fDFL3",
    //     "email" => "DFL3.julia@js-tech.tw",
    //     "mobile" => "886899364038",
    //     "last_session_id" => null,
    //     "is_block" => "unblock",
    //     "last_login_at" => null,
    //     "last_login_browser" => null,
    //     "last_login_device" => null,
    //     "last_login_system" => null,
    //     "remember_token" => null,
    //     "create_at" => "2020-05-22 15:14:41",
    //     "updated_at" => "2020-05-22 15:14:41",
    //     "deleted_at" => null,
    //     "remark" => null,
    //     "quick_menu" => null,


    // ];

    /**
     * 測試將原生注單轉換成整合注單
     *
     * @test
     */
    public function testConvert()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTickets = $this->rawTickets;

        // -----------
        //   Act
        // -----------
        $converter = new MgPokerConverter();
        $unitedTickets = $converter->transform($rawTickets);
dump($unitedTickets);
        // -----------
        //   Assert
        // -----------
        $this->assertEquals(count($unitedTickets), 2);

    }

    /**
     * 測試將原生注單轉換成整合注單
     *
     * @test
     */
    public function testUnitedTicketsReplaceInto()
    {
        // -----------
        //   Arrange
        // -----------
        $rawTickets = $this->rawTickets;

        // -----------
        //   Act
        // -----------
        $converter = new MgPokerConverter();
        $unitedTickets = $converter->transform($rawTickets);
        UnitedTicket::replace($unitedTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(UnitedTicket::count(), 2);

    }
}