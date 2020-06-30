<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;
use SuperPlatform\UnitedTicket\Models\BlockUnitedTicket;

$factory->define(BlockUnitedTicket::class, function (Faker $faker) {

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

    $rawTicketTableNameMapping = [
        'SUPER 體彩' => 'raw_ticket_super',
        'BOSS 彩票' => 'raw_ticket_boss',
        '沙龍' => 'raw_ticket_sa_gaming',
    ];

    $station = $faker->randomElement(array_keys($stationScopePlayWayMapping));
    $scope = $faker->randomElement(array_keys($stationScopePlayWayMapping[$station]));
    $playWay = $faker->randomElement($stationScopePlayWayMapping[$station][$scope]);
    $rawBet = $faker->randomFloat(2, 500, 1000);

    return [
        'id' => $faker->uuid(),
        'user_identify' => $faker->uuid(),
        'station' => $station,
        'game_scope' => $scope,
        'bet_type' => $playWay,

        'sum_raw_bet' => $rawBet,
        'sum_valid_bet' => $rawBet,
        'sum_rolling' => $rawBet,
        'sum_winnings' => $rawBet,

        'time_span_begin' => Carbon::now()->format('Y-m-d H:i:00'),
        'time_span_end' => Carbon::now()->format('Y-m-d H:i:00'),
        'created_at' => Carbon::now()->format('Y-m-d H:i:00'),
        'updated_at' => Carbon::now()->format('Y-m-d H:i:00'),
    ];
});
