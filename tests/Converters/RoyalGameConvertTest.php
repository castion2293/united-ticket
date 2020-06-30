<?php

use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Converters\RoyalGameConverter;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use SuperPlatform\StationWallet\StationWallet;

class RoyalGameConvertTest extends BaseTestCase
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

    private $rawTickets = [
        'tickets' => [
            // 原本正常單
            [
                "ID" => 136407746,
                "BucketID" => "super",
                "BatchRequestID" => "",
                "BetRequestID" => "c1916f224edc4abd80668e7489805825",
                "BetScore" => 500,
                "BetStatus" => 4,
                "BetTime" => "2019-05-21 15:21:55",
                "BucketPublicScore" => 0,
                "BucketRebateRate" => 0,
                "ClientIP" => "172.105.208.86",
                "ClientType" => "3",
                "Currency" => "NT",
                "CurrentScore" => 700,
                "DetailURL" => 'http://super-game.rg-show.com/OpenDetail?ServerID=1303120001&NoRun=150521003&NoActive=0211',
                "ExchangeRate" => 1,
                "FinalScore" => 500,
                "FundRate" => 0,
                'GameDepartmentID' => "RoyalGclub",
                "GameItem" => "LongHu",
                "GameType" => 1,
                "JackpotScore" => 0,
                "Member" => 'TT9B26D8F8',
                "MemberID" => 'TT9B26D8F8',
                "MemberName" => 'TT9B26D8F8',
                "NoRun" => 150521003,
                "NoActive" => 0211,
                "Odds" => 2,
                "OriginID" => 0,
                "OriginBetRequestID" => '',
                "LastBetRequestID" => '',
                "PortionRate" => 0,
                "ProviderID" => 'Royal',
                "PublicScore" => 0,
                "RebateRate" => 0,
                "RewardScore" => 0,
                "ServerID" => 1303120001,
                "ServerName" => 'LongHuA',
                "SettlementTime" => '2019-05-21 15:22:25',
                "StakeID" => '13031200010002',
                "StakeName" => 'Tiger',
                "SubClientType" => 's',
                "ValidBetScore" => 500,
                "WinScore" => 500,
                "TimeInt" => '2019052115',
                // 整合注單唯一值
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129741e"
            ],
            // 註銷單
            [
                "ID" => 136407869,
                "BucketID" => "super",
                "BatchRequestID" => "",
                "BetRequestID" => "c1916f224edc4abd80668e7489805825",
                "BetScore" => -500,
                "BetStatus" => 5,
                "BetTime" => "2019-05-21 15:21:55",
                "BucketPublicScore" => 0,
                "BucketRebateRate" => 0,
                "ClientIP" => "172.105.208.86",
                "ClientType" => "3",
                "Currency" => "NT",
                "CurrentScore" => 700,
                "DetailURL" => 'http://super-game.rg-show.com/OpenDetail?ServerID=1303120001&NoRun=150521003&NoActive=0211',
                "ExchangeRate" => 1,
                "FinalScore" => 500,
                "FundRate" => 0,
                'GameDepartmentID' => "RoyalGclub",
                "GameItem" => "LongHu",
                "GameType" => 1,
                "JackpotScore" => 0,
                "Member" => 'TT9B26D8F8',
                "MemberID" => 'TT9B26D8F8',
                "MemberName" => 'TT9B26D8F8',
                "NoRun" => 150521003,
                "NoActive" => 0211,
                "Odds" => 2,
                "OriginID" => 136407746,
                "OriginBetRequestID" => '',
                "LastBetRequestID" => '',
                "PortionRate" => 0,
                "ProviderID" => 'Royal',
                "PublicScore" => 0,
                "RebateRate" => 0,
                "RewardScore" => 0,
                "ServerID" => 1303120001,
                "ServerName" => 'LongHuA',
                "SettlementTime" => '2019-05-21 15:22:25',
                "StakeID" => '13031200010002',
                "StakeName" => 'Tiger',
                "SubClientType" => 's',
                "ValidBetScore" => -500,
                "WinScore" => 500,
                "TimeInt" => '2019052115',
                // 整合注單唯一值
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129742e"
            ],
            // 訂正單 (應取代於正常單的資料)
            [
                "ID" => 136407870,
                "BucketID" => "super",
                "BatchRequestID" => "",
                "BetRequestID" => "c1916f224edc4abd80668e7489805825",
                "BetScore" => 500,
                "BetStatus" => 5,
                "BetTime" => "2019-05-21 15:21:55",
                "BucketPublicScore" => 0,
                "BucketRebateRate" => 0,
                "ClientIP" => "172.105.208.86",
                "ClientType" => "3",
                "Currency" => "NT",
                "CurrentScore" => 700,
                "DetailURL" => 'http://super-game.rg-show.com/OpenDetail?ServerID=1303120001&NoRun=150521003&NoActive=0211',
                "ExchangeRate" => 1,
                "FinalScore" => -500,
                "FundRate" => 0,
                'GameDepartmentID' => "RoyalGclub",
                "GameItem" => "LongHu",
                "GameType" => 1,
                "JackpotScore" => 0,
                "Member" => 'TT9B26D8F8',
                "MemberID" => 'TT9B26D8F8',
                "MemberName" => 'TT9B26D8F8',
                "NoRun" => 150521003,
                "NoActive" => 0211,
                "Odds" => 2,
                "OriginID" => 136407746,
                "OriginBetRequestID" => '',
                "LastBetRequestID" => '',
                "PortionRate" => 0,
                "ProviderID" => 'Royal',
                "PublicScore" => 0,
                "RebateRate" => 0,
                "RewardScore" => 0,
                "ServerID" => 1303120001,
                "ServerName" => 'LongHuA',
                "SettlementTime" => '2019-05-21 15:22:25',
                "StakeID" => '13031200010002',
                "StakeName" => 'Tiger',
                "SubClientType" => 's',
                "ValidBetScore" => 500,
                "WinScore" => -500,
                "TimeInt" => '2019052115',
                // 整合注單唯一值
                "uuid" => "78cb4d76-67bb-3419-8b49-80788129731e"
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
        $converter = \App::make(RoyalGameConverter::class);
        $unitedTickets = $converter->transform($rawTickets);

        // -----------
        //   Assert
        // -----------

        // 總共有的筆數
        $this->assertEquals(count($unitedTickets), 3);

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
        // 轉換注單
        $converter = \App::make(RoyalGameConverter::class);

        $unitedTickets = $converter->transform($rawTickets);

        // 儲存整合注單
        UnitedTicket::replace($unitedTickets);

        // -----------
        //   Assert
        // -----------

        $this->assertEquals(UnitedTicket::count(), 370);

    }
}