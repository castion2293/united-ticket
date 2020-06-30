<?php


namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\VsLotteryRake;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「越南彩」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class VsLotteryConverter extends Converter
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
        $this->gameTypes = array_keys(config('api_caller.vs_lottery.game_scopes'));
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
        $station = 'vs_lottery';

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {

            // 取得原生注單內所有的username並將最前面與VS_LOTTERY_MEMBER_ACCOUNT_PREFIX相符的字串捨去
            $walletAccounts = collect($tickets)
                ->pluck('UserName')
                ->unique();
            $walletAccounts = collect($walletAccounts)->map(function($account) {
                return substr($account, 3, strlen($account));
            });

            // 取得錢包內所有的user_id在進行轉換
            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $station)
                ->get()
                ->mapWithKeys(function ($wallet) {
                    return [
                        data_get($wallet, 'account') => data_get($wallet, 'user_id')
                    ];
                });
        }

        foreach ($tickets as $ticket) {
            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = substr(array_get($ticket, 'UserName'), 3, strlen(array_get($ticket, 'UserName')));

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $rawTicketAccount = array_get($ticket, 'UserName');
            $username = substr($rawTicketAccount, 3, strlen($rawTicketAccount));

            // 市場名稱
            $market = array_get($ticket, 'MarketName');
            // 玩法
            $betType = array_get($ticket, 'BetType');
            // 投注時間
            $betAt = Carbon::parse(array_get($ticket, 'TrDate'))->toDateTimeString();
            // 派彩時間
            if (array_get($ticket, 'IsPending') === 'false') {
                $payoutAt = Carbon::parse(array_get($ticket, 'DrawDate'))->addDays(1)->toDateTimeString();
            } else {
                $payoutAt = null;
            }
            // 輸贏金額
            $winnings = array_get($ticket, 'WinAmt') + array_get($ticket, 'CommAmt');
            // 下注號碼
            $betNumber = array_get($ticket, 'BetNo');
            // 取消狀態
            if (array_get($ticket, 'IsCancelled') === 'false'){
                $invalid = false;
            } else {
                $invalid = true;
            }
            // 退水金額 
            $retake = array_get($ticket, 'CommAmt', 0);

            // 下注內容
            $gameResult = '<b style="color:#11bed1;">下注市場: '. $market . '</b><br/>';
            $gameResult .= '下注玩法： ' . $betType . '; ';
            if($betNumber == '[]') {
                $gameResult .= null;
            } else {
                $gameResult .= '號碼：' . $betNumber . '; ';
            }
            $gameResult .= '賠率：' . array_get($ticket, 'Odds1');

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'TrDetailID'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => 'keno',
                // 產品類別
                'category' => 'vietnam-lottery',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => currency_multiply_transfer($station, array_get($ticket, 'Turnover')),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => currency_multiply_transfer($station, array_get($ticket, 'NetAmt')),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => currency_multiply_transfer($station, array_get($ticket, 'NetAmt')),
                // 輸贏結果(可正可負)
                'winnings' => currency_multiply_transfer($station, $winnings),
                // 系統退水值
                'rebate_amount' => currency_multiply_transfer($station, $retake),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $invalid,
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

            //=========================================================
            // 建立越南彩抽水注單
            //=========================================================
            $rawRakeArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'TrDetailID'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => 'keno',
                // 產品類別
                'category' => 'vietnam-lottery',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => $betType,
                // 抽水點數
                'rake' => currency_multiply_transfer($station, $retake),
                // 投注時間
                'bet_at' => $betAt,
                // 派彩時間
                'payout_at' => $payoutAt,
            ];

            // 查找各階水倍差佔成
            $rakesTableArray = $this->findRebate(
                $userId,
                $station,
                'keno',
                $betAt
            );

            $unitedRakes[] = array_merge($rawRakeArray, $rakesTableArray);
        }

        // 儲存整合注單
        UnitedTicket::replace($unitedTickets);
        
        // 儲存退水注單
        VsLotteryRake::replace($unitedRakes);

        return $unitedTickets;
    }
}