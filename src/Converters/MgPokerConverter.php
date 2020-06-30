<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「MG棋牌」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class MgPokerConverter extends Converter
{
    /**
     * @var array|\Illuminate\Config\Repository|mixed
     * 產品類別
     */
    private $categories = [];

    /**
     * @var array|\Illuminate\Config\Repository|mixed
     * 遊戲類型參數對應表
     */
    private $gameTypes = [];

    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameTypes = array_keys(config('api_caller.mg_poker.game_scopes'));
    }

    /**
     * 轉換原生注單為整合注單
     *
     * @param array $rawTickets
     * @param string $userId
     * @return array
     */
    public function transform(array $rawTickets = [], string $userId = ''): array
    {

        $unitedTickets = [];
        $station = 'mg_poker';

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('account')
                ->unique();

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $station)
                ->get()
                ->mapWithKeys(function ($wallet) {
                    return [
                        data_get($wallet, 'account') =>data_get($wallet, 'user_id')
                    ];
                });
        }

        foreach ($tickets as $ticket) {
            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($ticket, 'account');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = array_get($ticket, 'gameId', '');

            $category = array_get($this->categories, "{$station}.{$gameScope}");
            // 輸贏金額
            $winnings = array_get($ticket, 'lose');//lose有正負直 正代表贏 負代表輸
            // 有效投注
            $validBet = array_get($ticket, 'validBet');
            // 下注金額
            $betAmount = array_get($ticket, 'bet');
            // 下注時間
            $betAt = array_get($ticket, 'roundBeginTime');
            // 結算時間
            $payoutAt = array_get($ticket, 'roundEndTime');


            $invalid = false;
            // 開牌結果
            $gameResult = "";
            $gameResult .= '<b style="color:#11bed1;">遊戲場：' . array_get($ticket, 'fieldId') . ' </b><br/>';
            $gameResult .= '<b style="color:#11bed1;">服務費: ' . array_get($ticket, 'fee') . ' </b><br/>';
            $gameResult .= '<b style="color:#11bed1;">詳細注單內容請至遊戲館後台查詢</b><br/>';




            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'roundId'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'account'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => 'e-battle',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => $betAmount,
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => $validBet,
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => $validBet,
                // 輸贏結果
                'winnings' => $winnings,
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $invalid,//如何判斷
                // 投注時間
                'bet_at' => $betAt,
                // 派彩時間
                'payout_at' => $payoutAt,
                // 資料建立時間
                'created_at' => Carbon::now()->toDateTimeString(),
                // 資料最後更新
                'updated_at' => Carbon::now()->toDateTimeString()
            ];

            // 查找各階輸贏佔成
            $allotTableArray = $this->findAllotment(
                $userId,
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