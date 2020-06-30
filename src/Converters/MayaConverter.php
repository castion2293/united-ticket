<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Config\Repository;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class MayaConverter extends Converter
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
        $this->gameType = config('united_ticket.maya.game_type');
        $this->types = config('united_ticket.maya.type');
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
            $betAt = array_get($ticket, 'BetDateTime', null);

            if (is_numeric($betNum = array_get($ticket, 'BetNo'))) {
                $betNum = strval($betNum);
            }

            $payoutAt = array_get($ticket, 'CountDateTime', null);
            $username = array_get($ticket, 'Username');
            $now = date('Y-m-d H:i:s');
            $station = 'maya';
            $game_scope = array_get($ticket, 'GameType');
            $category = array_get($this->categories, "{$station}.{$game_scope}");

            // 整理開牌結果

            $betDetail = array_get($this->gameType, "{$game_scope}.bet_type.{$ticket['BetDetail']}");
            $type = array_get($this->types, $ticket['BetType']);

            $gameResult = '<b style="color:#11bed1;">下注項目：' . $betDetail . ' </b>';

            if($game_scope === 'VipBaccarat') {
                $game_scope = 'VIPBaccarat';
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
                'category' => 'live',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($ticket, 'BetMoney'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'ValidBetMoney'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($ticket, 'ValidBetMoney'),
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'WinLoseMoney'),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => array_get($ticket, 'State') === 3,
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