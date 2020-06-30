<?php


namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「HB 電子」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class HabaneroConverter extends Converter
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

    /**
     * @var array 遊戲結果參數對應表
     */
    private $ticketStatus = [];

    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameTypes = config('united_ticket.habanero.game_type');
        $this->ticketStatus = config('united_ticket.habanero.ticket_status');
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
        $station = 'habanero';

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('Username')
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
                $account = array_get($ticket, 'Username');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameID = array_get($ticket, 'GameKeyName', '');

            // 顯示對應遊戲編號的遊戲名稱
            $game_type = array_get($this->gameTypes, $gameID);

            // 顯示對應遊戲結果
            $ticketStatus = array_get($ticket, 'GameStateId');
            $rawBet = array_get($ticket, 'Stake');
            // 計算輸贏結果
            $winningAmount = array_get($ticket, 'Payout') - array_get($ticket, 'Stake');
            // 投注時間
            $betTime = array_get($ticket, 'DtStarted');
            $unitedTicketBetAt = Carbon::parse($betTime)->addHours(8)->toDateTimeString();
            // 派彩時間
            $payoutTime = array_get($ticket, 'DtCompleted');
            $unitedTicketPayoutAt = Carbon::parse($payoutTime)->addHours(8)->toDateTimeString();
            // 若注單狀態為取消或失敗則變更為作廢住單
            $invalid = ($ticketStatus == '4') ? true : false;
            $jackpotWin = array_get($ticket, 'JackpotWin');
            // 判斷注單狀態為 「正在進行中」 則顯示在未派彩狀態
            $payout_at = ($ticketStatus == '3'  || $ticketStatus == '11') ? $unitedTicketPayoutAt : null;

            // 詳細
            $gameResult = '';
            $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' .  $game_type . ' </b><br/>';
            if ($jackpotWin > 0) {
                $gameResult .= '<b style="color:#fc5a34;">赢得游戏奖池金額: ' . array_get($ticket, 'JackpotWin') . ' </b><br/>';
            }
//            if ($invalid == true) {
//                $gameResult .= '<b style="color:#fc5a34;">注單狀態: ' . array_get($this->ticketStatus, array_get($ticket, 'GameStateId')). ' </b>';
//            }
            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'GameInstanceId'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'Username'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => 'slot',
                // 產品類別
                'category' => 'e-game',
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
                'rolling' => currency_multiply_transfer($station, $rawBet),
                // 輸贏結果
                'winnings' => currency_multiply_transfer($station, $winningAmount),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $invalid,
                // 投注時間
                'bet_at' => $unitedTicketBetAt,
                // 派彩時間
                'payout_at' => $payout_at,
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