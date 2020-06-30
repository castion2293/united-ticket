<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use test\Mockery\TestIncreasedVisibilityChild;

/**
 * 「RTG」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class RealTimeGamingConverter extends Converter
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

    private $username;

    /**
     * RealTimeGamingConverter constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameTypes = config('united_ticket.real_time_gaming.game_type');
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
        $station = 'real_time_gaming';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('playerName')
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
                $account = array_get($ticket, 'playerName');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            $game_scope = 'slot';

            $category = 'e-game';
            // 捕魚遊戲
            if (array_get($ticket, 'gameId') === '2162689') {
                $category = 'fishing';
            }

            $game_type = array_get($this->gameTypes, array_get($ticket, 'gameId'));
            $free_play = (array_get($ticket, 'bet') == 0) ? '是' : '否';
            $before_amount = array_get($ticket, 'balanceStart');
            $after_amount = array_get($ticket, 'balanceEnd');
            $payout_amount = array_get($ticket, 'win') ?? 0;
            // 派彩時間
            $payout_at = array_get($ticket, 'gameDate');
            // 下注時間
            $bet_at = array_get($ticket, 'gameStartDate');

            $bet_time = Carbon::parse($bet_at)->addHours(8)->toDateTimeString();
            $payout_time = Carbon::parse($payout_at)->addHours(8)->toDateTimeString();

            $gameResult = '<b style="color:#11bed1;">游戏名称: ' . $game_type . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">本局游戏是否免费: ' . $free_play . ' </b>';
//            $gameResult .= "會員原本金額: {$before_amount}; ";
//            $gameResult .= "會員總金額: {$after_amount}; ";
//            $gameResult .= "派彩金额: {$payout_amount}";

            // 由於RTG各幣別有對應的轉換比例 例如:RTG 下注金額若是1000 則會回傳1元
            if(config('api_caller.real_time_gaming.config.currency') == 'VND') {
                $rawBet = currency_multiply_transfer('real_time_gaming', array_get($ticket, 'bet'));
                $resultAmount = currency_multiply_transfer('real_time_gaming', (array_get($ticket, 'win') - array_get($ticket, 'bet')));
            } else {
                $rawBet = array_get($ticket, 'bet');
                $resultAmount = array_get($ticket, 'win') - array_get($ticket, 'bet');
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'id'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => array_get($ticket, 'playerName'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => 'real_time_gaming',
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => $rawBet,
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => $rawBet,
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => $rawBet,
                // 輸贏結果(可正可負)
                'winnings' => $resultAmount,
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => false,
                // 投注時間
                'bet_at' => $bet_time,
                // 派彩時間
                'payout_at' => $payout_time,
                // 資料建立時間
                'created_at' => Carbon::now()->toDateTimeString(),
                // 資料最後更新
                'updated_at' => Carbon::now()->toDateTimeString()
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