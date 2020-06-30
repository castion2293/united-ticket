<?php

use Faker\Generator as Faker;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;

$factory->define(AllBetTicket::class, function (Faker $faker) {

    $stationScopePlayWayMapping = [
        'SUPER 體彩' => [
            '美棒' => [
                '單式全場讓分', '單式全場大小', '走地全場讓分',
            ],
            '日棒' => [
                '單式全場讓分', '單式全場大小', '走地全場讓分',
            ],
            '台棒' => [
                '單式全場讓分', '單式全場大小', '走地全場讓分',
            ],
        ],
        'BOSS 彩票' => [
            'BingoBingo' => [
                '一般大小', '一般單雙', '超級大小', '超級單雙',
            ],
            '北京賽車' => [
                '冠軍大小單雙', '亞軍大小單雙', '第三名大小單雙',
            ],
        ],
        '沙龍' => [
            '百家樂' => [
                '一般'
            ],
            '輪盤' => [
                '一般'
            ],
            '骰寶' => [
                '一般'
            ],
        ]
    ];

    $station = $faker->randomElement(array_keys($stationScopePlayWayMapping));
    $scope = $faker->randomElement(array_keys($stationScopePlayWayMapping[$station]));
    $playWay = $faker->randomElement($stationScopePlayWayMapping[$station][$scope]);

    $time = \Carbon\Carbon::now()->toDateTimeString();
    $rawBet = $faker->randomFloat(2, 500, 1000);
    $winnings = $rawBet * $faker->randomFloat(2, -1, 2);

    return [
        //[PK] 資料識別碼
        'uuid' => $faker->uuid(),
        //建立時間
        'created_at' => $time,
        //最後更新
        'updated_at' => $time,
        //客戶用戶名
        'client' => $faker->asciify('******'),
        //客戶ID
        'client_id' => $faker->asciify('******'),
        //[UK]注單編號
        'betNum' => $faker->unique()->randomNumber(),
        //遊戲局編號
        'gameRoundId' => $faker->unique()->randomNumber(6),
        //遊戲類型
        'gameType' => $faker->randomNumber(2),
        //投注時間
        'betTime' => $time,
        //投注金額
        'betAmount' => $rawBet,
        //有效投注金額
        'validAmount' => $rawBet,
        //輸贏金額
        'winOrLoss' => $winnings,
        //注單狀態(0:正常 1:不正常)
        'state' => 0,
        //投注類型
        'betType' => 1,
        //開牌結果
        'gameResult' => '開牌結果',
        //遊戲結束時間
        'gameRoundEndTime' => $time,
        //遊戲開始時間
        'gameRoundStartTime' => $time,
        //桌台名稱
        'tableName' => $time,
        //桌台類型 (100:非免佣 1:免佣)
        'commission' => 1,
    ];
});
