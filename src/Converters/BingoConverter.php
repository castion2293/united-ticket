<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Config\Repository;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class BingoConverter extends Converter
{
    /**
     * @var array|Repository|mixed
     * 產品類別
     */
    private $categories = [];

    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->suit = config('united_ticket.bingo.suit');
        $this->betType = config('united_ticket.bingo.bet_type');
        $this->result = config('united_ticket.bingo.result');
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
            $betAt = array_get($ticket, 'bet_at', null);

            if (is_numeric($betNum = array_get($ticket, 'serial_no'))) {
                $betNum = strval($betNum);
            }

            $payoutAt = ($ticket['history'] !== 'null') ? array_get($ticket, 'adjust_at') : null;
            $username = array_get($ticket, 'account');
            $now = date('Y-m-d H:i:s');
            $station = 'bingo';
            $game_scope = 'bingo_star';
            $category = array_get($this->categories, "{$station}.{$game_scope}");

            // 整理派彩結果
            $results = array_first(json_decode(array_get($ticket, 'results'), true));

            $gameResult = '下注內容: {';
            $gameResult .= sprintf("玩法: %s; ", array_get($this->suit, array_get($ticket, 'bet_suit')));

            // 填入押注號碼(只有類型為星號與單猜才會有此欄位)
            $numbers = array_get($ticket, 'numbers');
            if (filled($numbers)) {
                $gameResult .= sprintf("押注號碼: %s; ", $numbers);
            }

            $gameResult .= sprintf("押注類型: %s; ", array_get($this->betType, array_get($results, 'suit')));
            $gameResult .= sprintf("賠率: %s; ", array_get($results, 'odds'));
            $gameResult .= sprintf("可贏金額: %s; ", array_get($results, 'winning'));
            $gameResult .= sprintf("開獎結果: %s; ", array_get($this->result, array_get($results, 'result')));
            $gameResult .= sprintf("注單序號: %s; ", array_get($results, 'serial_no'));
            $gameResult .= sprintf("期號: %s}; ", array_get($results, 'bingo_no'));

            $history = json_decode(array_get($ticket, 'history'), true);

            if (filled($history)) {
                $gameResult .= '開獎結果: {';
                $gameResult .= sprintf("開獎號碼: %s; ", json_encode(array_get($history, 'balls')));
                $gameResult .= sprintf("超級獎號: %s}", array_get($history, 'super_ball'));
            }

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
                'bet_type' => array_get($ticket, 'bet_suit'),
                // 實際投注
                'raw_bet' => array_get($ticket, 'bet'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'real_bet'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($ticket, 'real_bet'),
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'win_lose'),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢result
                'invalid' => array_get($ticket, 'result') === 'void',
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
}