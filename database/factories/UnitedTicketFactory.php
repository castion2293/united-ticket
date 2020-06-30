<?php

use Faker\Generator as Faker;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use Ramsey\Uuid\Uuid;

$factory->define(UnitedTicket::class, function (Faker $faker) {

    $stationScopePlayWayMapping = [
        'all_bet' => [
            // 普通百家樂
            'baccarat_ordinary' => [
                'general', // 通用
            ],
            // VIP 百家樂
            'baccarat_vip' => [
                'general', // 通用
            ],
            // 急速百家樂
            'baccarat_fast' => [
                'general', // 通用
            ],
            // 競咪百家樂
            'baccarat_compete' => [
                'general', // 通用
            ],
            // 骰寶
            'dice' => [
                'general', // 通用
            ],
            // 龍虎
            'dragon_tiger' => [
                'general', // 通用
            ],
            // 輪盤
            'roulette' => [
                'general', // 通用
            ],
            // 歐洲廳百家樂
            'baccarat_europe' => [
                'general', // 通用
            ],
            // 歐洲廳輪盤
            'roulette_europe' => [
                'general', // 通用
            ],
            // 歐洲廳 21 點
            'blackjack_europe' => [
                'general', // 通用
            ],
        ],
        'sa_gaming' => [
            // 百家樂
            'bac' => [
                'general', // 通用
            ],
            // 龍虎
            'dtx' => [
                'general', // 通用
            ],
            // 骰寶
            'sicbo' => [
                'general', // 通用
            ],
            // 翻攤
            'ftan' => [
                'general', // 通用
            ],
            // 輪盤
            'rot' => [
                'general', // 通用
            ],
            // 電子遊藝
            'slot' => [
                'general', // 通用
            ],
            // 48 彩 / 48 彩其他玩法
            'lottery' => [
                'general', // 通用
            ],
            // 小遊戲
            'minigame' => [
                'general', // 通用
            ],
        ],
        'bingo' => [
            // 賓果星
            'bingo_star' => [
                // 押星號
                'star',
                // 超級玩法(特別號)：單、雙
                'super_odd_even',
                // 超級玩法(特別號)：大、小
                'super_big_small',
                // 超級玩法(特別號)：獨猜
                'super_guess',
                // 一般玩法：單、雙、平
                'normal_odd_even_draw',
                // 一般玩法：大、小、合
                'normal_big_small_tie',
                // 五行
                'elements',
                // 四季
                'seasons',
                // 猜不出
                'other_fanbodan',
            ],
        ],
        'hy' => [
            // 百家樂
            'baccarat' => [
                'general', // 通用
            ],
            // 翻攤
            'fan_tan' => [
                'general', // 通用
            ],
            // 輪盤
            'roulette' => [
                'general', // 通用
            ],
            // 骰寶
            'sic_bo' => [
                'general', // 通用
            ],
            // 龍虎
            'lon_hwu' => [
                'general', // 通用
            ],
        ],
        'royal' => [
            // 百家樂/保險百家樂
            'bacc' => [
                'general', // 通用
            ],
            // 翻攤
            'fan_tan' => [
                'general', // 通用
            ],
            // 輪盤
            'lun_pan' => [
                'general', // 通用
            ],
            // 骰子
            'shai_zi' => [
                'general', // 通用
            ],
            // 魚蝦蟹
            'yu_xia_xie' => [
                'general', // 通用
            ],
            // 龍虎
            'long_hu' => [
                'general', // 通用
            ],
        ],
        'holdem' => [
            // 德州撲克
            'holdem_poker' => [
                'general', // 通用
            ],
        ],
        'super_sport' => [
            // 美棒
            'baseball_us' => [
                'general', // 通用
            ],
            // 日棒
            'baseball_jp' => [
                'general', // 通用
            ],
            // 台棒
            'baseball_tw' => [
                'general', // 通用
            ],
            // 韓棒
            'baseball_kr' => [
                'general', // 通用
            ],
            // 冰球
            'ice_hockey' => [
                'general', // 通用
            ],
            // 籃球
            'basketball' => [
                'general', // 通用
            ],
            // 美足（美式足球）
            'american_football' => [
                'general', // 通用
            ],
            // 網球
            'tennis' => [
                'general', // 通用
            ],
            // 足球（英式足球）
            'soccer' => [
                'general', // 通用
            ],
            // 指數
            'stock_market' => [
                'general', // 通用
            ],
            // 賽馬
            'horse_racing' => [
                'general', // 通用
            ],
            // 電競
            'e_sports' => [
                'general', // 通用
            ],
            // 其他
            'others' => [
                'general', // 通用
            ],
            // 世足
            'fifa_world_cup' => [
                'general', // 通用
            ],
        ],
    ];

    $user_identifies = [
        '000zmep32pm6hfn1bt1ttmtx4v',
        '000zmep3cqwke8cabcym5fdahb',
        '000zmep3q1k4meg5vc44esv8qg',
        '000zmep30w5kwyhsbrsygmztka',
        '000zmep4bh9232j19qc4ekkvhr',
        '000zmep4pmg5c0vvppds1hb52h',
        '000zmep51n1jaxaemm9s58431z',
        '000zmep5cqqx972p0v8cgq7v91',
        '000zmep5qb9asdktz10f60xmm2',
        '000zmep61bk8pw2kwkg1e12fgj',
        '000zmep6bvvtf0v2j5vaccjkzv',
        '000zmep6psy9am836z2d7sxga9',
        '000zmep60repmp7frqap68qrd0'
    ];

    $station_map = config('api_caller');
    $categories = config('api_caller_category');

    $station = $faker->randomElement(array_keys($station_map));
    $scope = $faker->randomElement(array_keys($station_map[$station]['game_scopes']));
    $category = array_get($categories, "{$station}.{$scope}");
    $playWay = $faker->randomElement($station_map[$station]['game_scopes'][$scope]);
    $rawBet = $faker->randomFloat(2, 500, 1000);
    $validBet = $rawBet;
    $distinctBet = $validBet;
    $winnings = $rawBet * $faker->randomFloat(2, 1, 2);

    $user_identify = $faker->randomElement($user_identifies);
    $bet_num = rand(10000000, 99999999);

    $uniqueFieldsData = [$user_identify, $bet_num];
    sort($uniqueFieldsData);
    $uniqueDataString = join('-', $uniqueFieldsData);
    $id = Uuid::uuid3(Uuid::NAMESPACE_DNS, $uniqueDataString . '@' . class_basename($this));

    return [
        'id' => $id,
        'bet_num' =>  $bet_num,
        'user_identify' => $user_identify,
        'username' => $faker->name(),
        'station' => $station,
        'game_scope' => $scope,
        'category' => $category,
        'bet_type' => $playWay,
        'raw_bet' => $rawBet,
        'valid_bet' => $validBet,
        'rolling' => $distinctBet,
        'winnings' => $winnings,
        'game_result' => 'resulte',
        'bet_at' => \Carbon\Carbon::now()->toDateTimeString(),
        'payout_at' => \Carbon\Carbon::now()->toDateTimeString(),
        'depth_0_identify' => 'root_id',
        'depth_0_ratio' => 1.00,
        'depth_1_identify' => $faker->randomElement(['user_1_id','user_2_id','user_3_id']),
        'depth_1_ratio' => 1.00,
        'depth_2_identify' => $faker->randomElement(['user_4_id','user_5_id','user_6_id']),
        'depth_2_ratio' => $faker->randomFloat(2, 0.97, 0.99),
        'depth_3_identify' => $faker->randomElement(['user_7_id','user_8_id','user_9_id']),
        'depth_3_ratio' => $faker->randomFloat(2, 0.92, 0.95),
        'depth_4_identify' => $faker->randomElement(['user_10_id','user_11_id','user_12_id']),
        'depth_4_ratio' => $faker->randomFloat(2, 0.90, 0.92),
        'depth_5_identify' => $faker->randomElement(['user_13_id','user_14_id','user_15_id']),
        'depth_5_ratio' => $faker->randomFloat(2, 0.87, 0.90),
        'depth_6_identify' => $faker->randomElement(['user_16_id','user_17_id','user_18_id']),
        'depth_6_ratio' => $faker->randomFloat(2, 0.85, 0.87),
        'depth_7_identify' => $faker->randomElement(['user_19_id','user_20_id','user_21_id']),
        'depth_7_ratio' => $faker->randomFloat(2, 0.82, 0.85),
        'depth_8_identify' => $faker->randomElement(['user_22_id','user_23_id','user_24_id']),
        'depth_8_ratio' => $faker->randomFloat(2, 0.80, 0.82),
//        'depth_9_identify' => $faker->randomElement(['user_25_id','user_26_id','user_27_id']),
//        'depth_9_ratio' => $faker->randomFloat(2, 0.77, 0.80),
    ];
});
