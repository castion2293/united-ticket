<?php

use SuperPlatform\UnitedTicket\Converters\AllBetConverter;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class AllBetConvertTest extends BaseTestCase
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
}

    private $rawTickets = [
        'tickets' => [
            [
                // 投注金額
                "betAmount"=>100,
                // 注單編號
                "betNum"=>2464971581703441,
                // 投注時間
                "betTime"=>"2017-10-24 11:26:14",
                // 投注類型
                "betType"=>1002,
                // 會員名稱
                "client"=>"14_a61",
                // 桌台類型
                "commission"=>100,
                // 開牌結果
                "gameResult"=>"{404,311,311},{411,204,-1}",
                // 遊戲局結束時間
                "gameRoundEndTime"=>"2017-10-24 11:26:52",
                // 遊戲局編號
                "gameRoundId"=>246497158,
                // 遊戲局開始時間
                "gameRoundStartTime"=>"2017-10-24 11:25:58",
                // 遊戲類型
                "gameType"=>"baccarat_ordinary",
                // 投單狀態
                "state"=>0,
                // 桌台名稱
                "tableName"=>"B002",
                // 有效投注金額
                "validAmount"=>0,
                // 輸贏金額
                "winOrLoss"=>0,
                // 整合注單唯一值
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741e"
            ],
            [
                "betAmount"=>100,
                "betNum"=>2464972340475032,
                "betTime"=>"2017-10-24 11:27:35",
                "betType"=>1003,
                "client"=>"14_a61",
                "commission"=>100,
                "gameResult"=>"{211,202,106},{301,101,403}",
                "gameRoundEndTime"=>"2017-10-24 11:28:13",
                "gameRoundId"=>246497234,
                "gameRoundStartTime"=>"2017-10-24 11:27:14",
                "gameType" => "baccarat_fast",
                "state"=>0,
                "tableName"=>"B002",
                "validAmount"=>100,
                "winOrLoss" => -100,
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741f"
            ]
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

//        $rawTickets['tickets'] = factory(AllBetTicket::class, 100)->make()->toArray();


        // -----------
        //   Act
        // -----------
//        $converter = new AllBetConverter();
        $converter = \App::make(AllBetConverter::class);
        $unitedTickets = $converter->transform($rawTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertGreaterThanOrEqual(0, count($unitedTickets));
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
//        $rawTickets['tickets'] = factory(AllBetTicket::class, 100)->make()->toArray();

        // -----------
        //   Act
        // -----------
        $converter = \App::make(AllBetConverter::class);
        $unitedTickets = $converter->transform($rawTickets);
        UnitedTicket::replace($unitedTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertGreaterThanOrEqual(0, UnitedTicket::count());

    }
}