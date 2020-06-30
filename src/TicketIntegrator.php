<?php

namespace SuperPlatform\UnitedTicket;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketIntegrator
{
    /**
     * 取得遊戲站的「遊戲範疇」「押注類型」配對表
     *
     * @param string|null $station
     * @return array
     */
    public function catalog(string $station = null)
    {
        $table = [];
        foreach (config("ticket_integrator", []) as $stationIdentify => $settings) {
            $table[$stationIdentify] = [];
            foreach (array_get($settings, 'catalog', []) as $gameScope => $betTypes) {
                $table[$stationIdentify][$gameScope] = $betTypes;
            }
        }
        return empty($station) ? $table : array_get($table, $station);
    }

    /**
     * $blockUnitedTickets = UnitedTicket::timeSpanBlock('station', [
     * '2017-08-01 12:13:14',
     * '2017-08-02 22:23:24',
     * '2017-08-03 04:44:32'
     * ])
     * 資料欄位 | user_identify | station   | game_scope | bet_type | sum_raw_bet | sum_valid_bet | sum_rolling | sum_winnings | time_span_begin     | time_span_end       |
     */
    public function timeSpanBlock($station, $user_identify, $timeDatas)
    {
        if (!is_array($timeDatas)) {
            return false;
        }

        if (2 > count($timeDatas)) {
            return false;
        }

        Schema::create('tmp_store_block', function (Blueprint $table) {
            $table->string('user_identify');
            $table->string('station');
            $table->string('game_scope');
            $table->string('bet_type');
            $table->decimal('sum_raw_bet', 10, 2);
            $table->decimal('sum_valid_bet', 10, 2);
            $table->decimal('sum_rolling', 10, 2);
            $table->decimal('sum_winnings', 10, 2);
            $table->string('timespan');
            $table->temporary();
        });

        $end = '';
        $i = 0;
        foreach ($timeDatas as $time) {
            $start = $end;
            $end = Carbon::parse($time);

            if ($i === 0) {
                $i++;
                continue;
            }

            // 第一個時間點之後最近的區塊時間起點
            $startEndPoint = $start->copy()->addMinutes(15 - $start->minute % 15)->subSecond($start->second);
            // 最後的時間點之前最近的區塊時間終點
            $endStartPoint = $end->copy()->subMinutes($end->minute % 15)->subSecond($end->second);

            // 判斷是否需要使用預合計表
            if ($startEndPoint->lt($endStartPoint)) {
                $sql = "INSERT INTO tmp_store_block
                    SELECT 
                    user_identify, station, game_scope, bet_type,
                    SUM(sum_raw_bet) AS sum_raw_bet,
                    SUM(sum_valid_bet) AS sum_valid_bet,
                    SUM(sum_rolling) AS sum_rolling,
                    SUM(sum_winnings) AS sum_winnings,
                    '" . $start->format('Y-m-d H:i:s') . " ~ " . $end->format('Y-m-d H:i:s') . "' AS `timespan`
                    FROM block_united_tickets WHERE
                    station='" . $station . "'
                    AND user_identify='" . $user_identify . "'
                    AND time_span_begin >= '" . $startEndPoint->format('Y-m-d H:i:s') . "'
                    AND time_span_begin < '" . $endStartPoint->format('Y-m-d H:i:s') . "'
                    GROUP BY user_identify, station, game_scope, bet_type
                    UNION
                    SELECT
                    user_identify, station, game_scope, bet_type,
                    SUM(raw_bet) AS sum_raw_bet,
                    SUM(valid_bet) AS sum_valid_bet,
                    SUM(rolling) AS sum_rolling,
                    SUM(winnings) AS sum_winnings,
                    '" . $start->format('Y-m-d H:i:s') . " ~ " . $end->format('Y-m-d H:i:s') . "' AS timespan
                    FROM united_tickets WHERE
                    station='" . $station . "'
                    AND user_identify='" . $user_identify . "'
                    AND bet_at >= '" . $startEndPoint->format('Y-m-d H:i:s') . "'
                    AND bet_at < '" . $endStartPoint->format('Y-m-d H:i:s') . "'
                    GROUP BY user_identify, station, game_scope, bet_type
                    UNION
                    SELECT
                    user_identify, station, game_scope, bet_type,
                    SUM(raw_bet) AS sum_raw_bet,
                    SUM(valid_bet) AS sum_valid_bet,
                    SUM(rolling) AS sum_rolling,
                    SUM(winnings) AS sum_winnings,
                    '" . $start->format('Y-m-d H:i:s') . " ~ " . $end->format('Y-m-d H:i:s') . "' AS timespan
                    FROM united_tickets WHERE
                    station='" . $station . "'
                    AND user_identify='" . $user_identify . "'
                    AND bet_at >= '" . $startEndPoint->format('Y-m-d H:i:s') . "'
                    AND bet_at < '" . $endStartPoint->format('Y-m-d H:i:s') . "'
                    GROUP BY user_identify, station, game_scope, bet_type";
                DB::insert( DB::raw( $sql ));
                continue;
            }

            $sql = "INSERT INTO tmp_store_block
                    SELECT
                    user_identify, station, game_scope, bet_type,
                    SUM(raw_bet) AS sum_raw_bet,
                    SUM(valid_bet) AS sum_valid_bet,
                    SUM(rolling) AS sum_rolling,
                    SUM(winnings) AS sum_winnings,
                    '" . $start->format('Y-m-d H:i:s') . " ~ " . $end->format('Y-m-d H:i:s') . "' AS timespan
                    FROM united_tickets WHERE
                    station='" . $station . "'
                    AND user_identify='" . $user_identify . "'
                    AND bet_at >= '" . $start->format('Y-m-d H:i:s') . "'
                    AND bet_at < '" . $end->format('Y-m-d H:i:s') . "'
                    GROUP BY user_identify, station, game_scope, bet_type";

            DB::insert( DB::raw( $sql ));
        }

        return DB::table('tmp_store_block')
            ->select('*')
            ->groupBy('user_identify', 'station', 'game_scope', 'bet_type', 'timespan')
            ->get();
    }
}