<?php

namespace SuperPlatform\UnitedTicket;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use SuperPlatform\UnitedTicket\Models\BlockUnitedTicket as BUT;
use Ramsey\Uuid\Uuid;

class BlockUnitedTicket
{
    private $united_ticket_ids = [];

    public function storeBlock($station, $start = '', $end = '')
    {

        // 如果沒有指定時間區間，就以上一刻鐘為主
        $timeStart = empty($start) ? now()->subMinutes(now()->minute % 15)->subMinutes(15)->format('Y-m-d H:i:00') : $start;
        $timeEnd = empty($end) ? now()->subMinutes(now()->minute % 15)->format('Y-m-d H:i:00') : $end;

        // 從united_tickets table 取出注單並使用user_identify，station，game_scope，bet_type，depth_identify .... 做分類
        $block = $this->selectBlock($station, $timeStart, $timeEnd);

        // 使用user_identify，station，game_scope，bet_type，depth_identify組合成整合項目的id
        $block = $this->blockTransform($block, $timeStart);

        // 把tickets加到daily，weekly，monthly redis，供營運交收報表使用
//        foreach ($block as $blockTicket) {
//            Redis::command('lpush', ['today_tickets', json_encode($blockTicket)]);
//        }
//
//        foreach ($block as $blockTicket) {
//            Redis::command('lpush', ['this_week_tickets', json_encode($blockTicket)]);
//        }
//
//        foreach ($block as $blockTicket) {
//            Redis::command('lpush', ['this_month_tickets', json_encode($blockTicket)]);
//        }

        if ($block->isNotEmpty()) {
            // 把tickets存進MYSQL，保存為將來使用
            BUT::replace(
                json_decode($block->toJson(), true)
            );

//            // 修改壓縮過的注單狀態為 true
//            DB::table('united_tickets')
//                ->whereIn('id', $this->united_ticket_ids)
//                ->update(['compressed' => true]);
        }


//        Redis::command('del', ['tickets']);
    }

    /**
     * @param $station
     * @param $timeStart
     * @param $timeEnd
     * @return mixed
     */
    private function selectBlock($station, $timeStart, $timeEnd)
    {
        //---------------------------------------以下是使用REDIS，速度比較快------------------------------------------------------------------------
//        $tickets = collect(Redis::command('lrange', ['tickets', 0, -1]));
//
//        return $tickets->map(function ($ticket) {
//            return json_decode($ticket);
//        })
//        ->groupBy(['user_identify', 'station', 'game_scope',
//                'depth_1_identify', 'depth_2_identify', 'depth_3_identify',
//                'depth_4_identify', 'depth_5_identify', 'depth_6_identify',
//                'depth_7_identify', 'depth_8_identify', 'depth_9_identify',
//                'depth_10_identify', 'depth_11_identify', 'depth_12_identify'
//        ])->flatten(14) // 取到最底層的上一層
//        ->map(function ($blockTickets) use ($timeStart, $timeEnd) {
//            $sum_raw_bet = $blockTickets->sum('raw_bet');
//            $sum_valid_bet = $blockTickets->sum('valid_bet');
//            $sum_rolling = $blockTickets->sum('rolling');
//            $sum_winnings = $blockTickets->sum('winnings');
//            $sum_bonus = $blockTickets->sum('bonus');
//
//            $first = $blockTickets->shift();
//
//            return [
//                'user_identify' => $first->user_identify,
//                'station' => $first->station,
//                'game_scope' => $first->game_scope,
//                'category' => $first->category,
//                'bet_type' => $first->bet_type,
//                'sum_raw_bet' => $sum_raw_bet,
//                'sum_valid_bet' => $sum_valid_bet,
//                'sum_rolling' => $sum_rolling,
//                'sum_winnings' => $sum_winnings,
//                'sum_bonus' => $sum_bonus,
//                'time_span_begin' => $timeStart,
//                'time_span_end' => $timeEnd,
//                'depth_0_identify' => isset($first->depth_0_identify) ? $first->depth_0_identify : '',
//                'depth_0_ratio' => isset($first->depth_0_ratio) ? $first->depth_0_ratio : 0.0,
//                'depth_1_identify' => isset($first->depth_1_identify) ? $first->depth_1_identify : '',
//                'depth_1_ratio' => isset($first->depth_1_ratio) ? $first->depth_1_ratio : 0.0,
//                'depth_2_identify' => isset($first->depth_2_identify) ? $first->depth_2_identify : '',
//                'depth_2_ratio' => isset($first->depth_2_ratio) ? $first->depth_2_ratio : 0.0,
//                'depth_3_identify' => isset($first->depth_3_identify) ? $first->depth_3_identify : '',
//                'depth_3_ratio' => isset($first->depth_3_ratio) ? $first->depth_3_ratio : 0.0,
//                'depth_4_identify' => isset($first->depth_4_identify) ? $first->depth_4_identify : '',
//                'depth_4_ratio' => isset($first->depth_4_ratio) ? $first->depth_4_ratio : 0.0,
//                'depth_5_identify' => isset($first->depth_5_identify) ? $first->depth_5_identify : '',
//                'depth_5_ratio' => isset($first->depth_5_ratio) ? $first->depth_5_ratio : 0.0,
//                'depth_6_identify' => isset($first->depth_6_identify) ? $first->depth_6_identify : '',
//                'depth_6_ratio' => isset($first->depth_6_ratio) ? $first->depth_6_ratio : 0.0,
//                'depth_7_identify' => isset($first->depth_7_identify) ? $first->depth_7_identify : '',
//                'depth_7_ratio' => isset($first->depth_7_ratio) ? $first->depth_7_ratio : 0.0,
//                'depth_8_identify' => isset($first->depth_8_identify) ? $first->depth_8_identify : '',
//                'depth_8_ratio' => isset($first->depth_8_ratio) ? $first->depth_8_ratio : 0.0,
//                'depth_9_identify' => isset($first->depth_9_identify) ? $first->depth_9_identify : '',
//                'depth_9_ratio' => isset($first->depth_9_ratio) ? $first->depth_9_ratio : 0.0,
//                'depth_10_identify' => isset($first->depth_10_identify) ? $first->depth_10_identify : '',
//                'depth_10_ratio' => isset($first->depth_10_ratio) ? $first->depth_10_ratio : 0.0,
//                'depth_11_identify' => isset($first->depth_11_identify) ? $first->depth_11_identify : '',
//                'depth_11_ratio' => isset($first->depth_11_ratio) ? $first->depth_11_ratio : 0.0,
//                'depth_12_identify' => isset($first->depth_12_identify) ? $first->depth_12_identify : '',
//                'depth_12_ratio' => isset($first->depth_12_ratio) ?  $first->depth_12_ratio : 0.0,
//            ];
//        });

        //---------------------------------------以下是使用SQL語法，速度比較慢------------------------------------------------------------------------

//        $united_tickets = DB::table('united_tickets')
//            ->where('invalid', false) // 未作廢，正常住單
//            ->where('compressed', false) // 未壓縮
//            ->where('payout_at', '<>', null) // 已開彩
//            ->where('station', '=', $station);
//
//        $this->united_ticket_ids = $united_tickets->get()->pluck('id');

        return DB::table('united_tickets')
            ->select(
                'user_identify', 'username', 'station', 'game_scope', 'bet_type', 'category',
                DB::raw('user_identify'),
                DB::raw('COUNT(id) AS ticket_count'),
                DB::raw('SUM(raw_bet) AS sum_raw_bet'),
                DB::raw('SUM(valid_bet) AS sum_valid_bet'),
                DB::raw('SUM(rolling) AS sum_rolling'),
                DB::raw('SUM(winnings) AS sum_winnings'),
                DB::raw('SUM(bonus) AS sum_bonus'),
                DB::raw('\'' . $timeStart . '\' AS time_span_begin'),
                DB::raw('\'' . $timeEnd . '\' AS time_span_end'),
                'depth_0_identify', 'depth_0_ratio',
                'depth_1_identify', 'depth_1_ratio',
                'depth_2_identify', 'depth_2_ratio',
                'depth_3_identify', 'depth_3_ratio',
                'depth_4_identify', 'depth_4_ratio',
                'depth_5_identify', 'depth_5_ratio',
                'depth_6_identify', 'depth_6_ratio',
                'depth_7_identify', 'depth_7_ratio',
                'depth_8_identify', 'depth_8_ratio',
                'depth_9_identify', 'depth_9_ratio',
                'depth_10_identify', 'depth_10_ratio'
//                'depth_11_identify', 'depth_11_ratio',
//                'depth_12_identify', 'depth_12_ratio'
            )
            ->groupBy(
                'user_identify', 'username', 'station', 'game_scope', 'bet_type', 'category',
                'depth_0_identify', 'depth_0_ratio',
                'depth_1_identify', 'depth_1_ratio',
                'depth_2_identify', 'depth_2_ratio',
                'depth_3_identify', 'depth_3_ratio',
                'depth_4_identify', 'depth_4_ratio',
                'depth_5_identify', 'depth_5_ratio',
                'depth_6_identify', 'depth_6_ratio',
                'depth_7_identify', 'depth_7_ratio',
                'depth_8_identify', 'depth_8_ratio',
                'depth_9_identify', 'depth_9_ratio',
                'depth_10_identify', 'depth_10_ratio'
//                'depth_11_identify', 'depth_11_ratio',
//                'depth_12_identify', 'depth_12_ratio'
            )
            ->where('invalid', false) // 未作廢，正常住單
            ->where('payout_at', '<>', null) // 已開彩
            ->where('payout_at', '>=', $timeStart)
            ->where('payout_at', '<', $timeEnd)
            ->where('station', '=', $station)
            ->get();
    }

    /**
     * @param $block
     * @param $timeStart
     * @return mixed
     */
    private function blockTransform($block, $timeStart)
    {
        return $block->transform(function ($item, $key) use ($timeStart) {
            $depth = '';

            for ($i = 0; $i < 13; $i++) {
                $depth_identify = 'depth_' . $i . '_identify';
                $depth = $depth . (empty($item->$depth_identify) ? '' : '-' . $item->$depth_identify);
            }

            $uniqueFieldsData = [
                $item->user_identify,
                $item->username,
                $item->station,
                $item->game_scope,
                $item->bet_type,
                $item->category,
                $item->time_span_begin,
                $item->time_span_end,
                $item->depth_0_identify,
                $item->depth_0_ratio,
                $item->depth_1_identify,
                $item->depth_1_ratio,
                $item->depth_2_identify,
                $item->depth_2_ratio,
                $item->depth_3_identify,
                $item->depth_3_ratio,
                $item->depth_4_identify,
                $item->depth_4_ratio,
                $item->depth_5_identify,
                $item->depth_5_ratio,
                $item->depth_6_identify,
                $item->depth_6_ratio,
                $item->depth_7_identify,
                $item->depth_7_ratio,
                $item->depth_8_identify,
                $item->depth_8_ratio,
                $item->depth_9_identify,
                $item->depth_9_ratio,
                $item->depth_10_identify,
                $item->depth_10_ratio,
//                $item->depth_11_identify,
//                $item->depth_11_ratio,
//                $item->depth_12_identify,
//                $item->depth_12_ratio,
            ];

            sort($uniqueFieldsData);
            $uniqueDataString = join('-', $uniqueFieldsData);

            $id = Uuid::uuid3(Uuid::NAMESPACE_DNS, $uniqueDataString . '@' . class_basename($this));

//            $item['id'] = $item['station'] . '-' . $item['game_scope'] . '-' . $item['bet_type'] . '-' . $item['user_identify'] . '-' . preg_replace('/[(\s)(:)]/i', '-', $timeStart) . $depth;

            $item->id = $id;
            return $item;
        });
    }
}