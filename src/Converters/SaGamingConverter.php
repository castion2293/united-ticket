<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Config\Repository;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「沙龍」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class SaGamingConverter extends Converter
{
    /**
     * @var array|Repository|mixed
     * 產品類別
     */
    private $categories = [];

    /**
     * @var array|Repository|mixed
     * 遊戲類型參數對應表
     */
    private $gameTypes = [];

    /**
     * @var array
     * 撲克牌花色
     */
    private $flusher = [];

    /**
     * @var array
     * 撲克牌點數
     */
    private $numbers = [];

    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.sa_gaming.game_type');
        $this->categories = config('api_caller_category');

        $this->flusher = [
            '1' => '黑桃', // spades
            '2' => '紅心', // hearts
            '3' => '梅花', // clubs
            '4' => '方塊' // diamonds
        ];

        $this->numbers = [
            '1' => 'A',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            '11' => 'J',
            '12' => 'Q',
            '13' => 'K',
        ];
    }

    /**
     * 轉換 (注意： 僅轉換，未寫入資料庫)
     *
     * @param array $aRawTickets
     * @param string $sUserId
     * @return array
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $unitedTickets = [];

        foreach (array_get($aRawTickets, 'tickets', []) as $ticket) {
            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($ticket, 'BetTime', null);

            if (is_numeric($betNum = array_get($ticket, 'BetID'))) {
                $betNum = strval($betNum);
            }

            $payoutAt = array_get($ticket, 'PayoutTime', null);
            $username = array_get($ticket, 'Username');
            $now = date('Y-m-d H:i:s');
            $station = 'sa_gaming';
            $game_scope = array_get($ticket, 'GameType');
            $category = array_get($this->categories, "{$station}.{$game_scope}");
            $rawBet = array_get($ticket, 'BetAmount');
            $rolling = array_get($ticket, 'Rolling');
            $winnings = array_get($ticket, 'ResultAmount');

            // 整理開牌結果
            if (filled($game_scope) && method_exists($this, $game_scope)) {
                $gameResult = $this->$game_scope($ticket, $game_scope);
            } else {
                $gameResult = '';
            }

            /**
             * --------------------------------------------------------------------
             * |       整合注單          |        沙龍原始注單
             * --------------------------------------------------------------------
             * | 資料識別碼(id)          |  用戶名(Username)與投注記錄ID(BetID)取uuid
             * --------------------------------------------------------------------
             * | 原生注單編碼(bet_num)    | 投注記錄ID(BetID)
             * --------------------------------------------------------------------
             * | 類型(game_scope)        | 遊戲類型(GameType)
             * --------------------------------------------------------------------
             * | 實際投注(raw_bet)       | 投注額(BetAmount)
             * --------------------------------------------------------------------
             * | 有效投注(valid_bet)     | 投注額(BetAmount)
             * --------------------------------------------------------------------
             * | 洗碼量(rolling)         | 洗碼量(Rolling)
             * --------------------------------------------------------------------
             * | 輸贏結果(winnings)      | 輸贏金額(ResultAmount) 有正負
             * --------------------------------------------------------------------
             * | 開牌結果(game_result)   | 遊戲結果(GameResult)
             * --------------------------------------------------------------------
             * | 投注時間(bet_at)        | 投注時間(BetTime)
             * --------------------------------------------------------------------
             * | 派彩時間(payout_at)     | 結算時間(PayoutTime)
             * --------------------------------------------------------------------
             */
            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => $betNum,
                // 會員帳號識別碼$user_id$userIdentify,
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => currency_multiply_transfer($station, $rawBet),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => currency_multiply_transfer($station, $rawBet),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => currency_multiply_transfer($station, $rolling),
                // 輸贏結果(可正可負)
                'winnings' => currency_multiply_transfer($station, $winnings),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => false,
                // 投注時間
                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
                // 派彩時間
                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now
            ];

            // 查找各階輸贏佔成
            $allotTableArray = $this->findAllotment(
                $sUserId,
                $station,
                $rawTicketArray['game_scope'],
                $rawTicketArray['bet_at']
            );

            $unitedTickets[] = array_merge($rawTicketArray, $allotTableArray);
        }

        // 儲存整合注單
        UnitedTicket::replace($unitedTickets);

        return $unitedTickets;
    }

    /**
     * @param $ticket
     * @param null $game_scope
     * @return string
     * 百家樂得開牌結果轉換function
     */
    public function bac($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'BaccaratResult');
        $game_results = array_get($this->gameTypes, 'bac.game_result');

        $play = $this->getCardResult($results, 'PlayerCard');
        $bank = $this->getCardResult($results, 'BankerCard');

        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->keys()
            ->map(
                function ($item) use ($game_results) {
                    return array_get($game_results, $item);
                }
            )
            ->filter(
                function ($item) {
                    return filled($item);
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item . ',';
                }
            );

        $resultDetail = sprintf("{%s}", preg_replace('/,+$/', '', $resultDetail));

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '閒家: ' . $play . '; ';
        $gameResult .= '莊家: ' . $bank;

        return $gameResult;
    }

    /**
     * @param $ticket
     * @param null $game_scope
     * @return mixed
     * 電子遊戲的開牌結果轉換function
     */
    public function slot($ticket, $game_scope = null)
    {
        $details = array_get($this->gameTypes, 'slot.detail');
        $gameResult = sprintf("遊戲名稱: %s", array_get($details, str_replace('"', '', $ticket['Detail']), ''));

        return $gameResult;
    }

    /**
     * @param $ticket
     * @param null $game_scope
     * @return string
     * 龍虎的開牌結果轉換function
     */
    public function dtx($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'DragonTigerResult');
        $game_results = array_get($this->gameTypes, 'dtx.game_result');

        $dragon = $this->transferCardResult(array_get($results, 'DragonCard'));
        $tiger = $this->transferCardResult(array_get($results, 'TigerCard'));

        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->keys()
            ->map(
                function ($item) use ($game_results) {
                    return array_get($game_results, $item);
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item;
                }
            );

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '龍: ' . $dragon . '; ';
        $gameResult .= '虎: ' . $tiger;

        return $gameResult;
    }

    /**
     * @param $ticket
     * @param null $game_scope
     * @return string
     * 輪盤的開牌結果轉換function
     */
    public function rot($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'RouletteResult');
        $game_results = array_get($this->gameTypes, 'rot.game_result');

        $points = array_get($results, 'Point');
        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->keys()
            ->map(
                function ($item) use ($game_results) {
                    return array_get($game_results, $item);
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item . ',';
                }
            );

        $resultDetail = sprintf("{%s}", preg_replace('/,+$/', '', $resultDetail));

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '點數： ' . $points;

        return $gameResult;
    }

    /**
     * @param $ticket
     * @param null $game_scope
     * @return string
     * 骰寶的開牌結果轉換
     */
    public function sicbo($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'SicboResult');
        $game_results = array_get($this->gameTypes, 'sicbo.game_result');

        $dice1 = array_get($results, 'Dice1');
        $dice2 = array_get($results, 'Dice2');
        $dice3 = array_get($results, 'Dice3');
        $totalPoint = array_get($results, 'TotalPoint');

        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->map(
                function ($value, $key) use ($game_results) {
                    $detail = array_get($game_results, $key);

                    if (is_string($detail)) {
                        return $detail;
                    }

                    return array_get($detail, $value);
                }
            )
            ->filter(
                function ($item) {
                    return $item !== 'NA';
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item . ',';
                }
            );

        $resultDetail = sprintf("{%s}", preg_replace('/,+$/', '', $resultDetail));

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '骰子1: ' . $dice1 . "; ";
        $gameResult .= "骰子2: " . $dice2 . "; ";
        $gameResult .= "骰子3: " . $dice3 . "; ";
        $gameResult .= "總點數: " . $totalPoint;

        return $gameResult;
    }

    public function ftan($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'FantanResult');
        $game_results = array_get($this->gameTypes, 'ftan.game_result');

        $point = array_get($results, 'Point');

        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->keys()
            ->map(
                function ($item) use ($game_results) {
                    return array_get($game_results, $item);
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item . ',';
                }
            );

        $resultDetail = sprintf("{%s}", preg_replace('/,+$/', '', $resultDetail));

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '點數: ' . $point;

        return $gameResult;
    }

    public function minigame($ticket, $game_scope = null)
    {
        $gameResult = array_get($ticket, 'Detail');

        return $gameResult;
    }

    public function multiplayer($ticket, $game_scope = null)
    {
        $details = array_get($this->gameTypes, 'multiplayer');

        $detail = array_get($ticket, 'Detail');

        $gameResult = sprintf("遊戲名稱: %s", array_get($details, str_replace('"', '', $detail)));

        return $gameResult;
    }

    public function moneywheel($ticket, $game_scope = null)
    {
        $bet_type = array_get($this->gameTypes, "{$game_scope}.bet_type.{$ticket['BetType']}");
        $results = array_get(json_decode($ticket['GameResult'], true), 'MoneyWheelResult');
        $game_results = array_get($this->gameTypes, 'moneywheel.game_result');
        $resultDetail = collect($results['ResultDetail'])->filter(
            function ($item) {
                return $item !== 'false';
            }
        )
            ->keys()
            ->map(
                function ($item) use ($game_results) {
                    return array_get($game_results, $item);
                }
            )
            ->reduce(
                function ($carry, $item) {
                    return $carry . $item . ',';
                }
            );

        $resultDetail = sprintf("{%s}", preg_replace('/,+$/', '', $resultDetail));

        $gameResult = '<b style="color:#11bed1;">下注項目: ' . $bet_type . '</b><br/>';
        $gameResult .= '贏方: ' . $resultDetail;

        return $gameResult;
    }

    /**
     * @param null $results
     * @param string $role
     * @return string
     */
    private function getCardResult($results = null, $role = '')
    {
        $card_result_string = '';

        for ($i = 1; $i < 4; $i++) {
            $playerCard = $this->transferCardResult(array_get($results, $role . $i, null));

            if (filled($playerCard)) {
                $card_result_string .= $playerCard . ',';
            }
        }

        $card_result_string = preg_replace('/,+$/', '', $card_result_string);

        return '[' . $card_result_string . ']';
    }

    /**
     * @param null $card
     * @return null|string
     */
    private function transferCardResult($card = null)
    {
        if (blank($card)) {
            return null;
        }

        $suit = array_get($this->flusher, array_get($card, 'Suit'));
        $rank = array_get($this->numbers, array_get($card, 'Rank'));

        return $suit . $rank;
    }
}