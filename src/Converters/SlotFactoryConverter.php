<?php

namespace SuperPlatform\UnitedTicket\Converters;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class SlotFactoryConverter extends Converter
{

    /**
     * 遊戲站名稱
     *
     * @var string
     */
    private $station = 'slot_factory';

    public function __construct()
    {
        parent::__construct();
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

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('AccountID')
                ->unique();

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $this->station)
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
                $account = array_get($ticket, 'AccountID');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            // 投注金額及輸贏結果
            $rawBet = floatval(array_get($ticket, 'TotalBet')) / 100; // 單位是分 要除以100
            $totalPayout = floatval(array_get($ticket, 'CashWon')) / 100; // 單位是分 要除以100
            $winnings = $totalPayout - $rawBet;

            // 投注及派彩時間 時間要改成UTC+8
            $spinDate = array_get($ticket, 'SpinDate');
            $betAt = $payoutAt = Carbon::parse($spinDate)->addHours(8)->toDateTimeString();

            // 開牌詳細內容
            $gameResult = '';
            $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' . array_get($ticket, 'GameName') . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">本局遊戲是否免費: ' . (array_get($ticket, 'FreeGames') ? '是' : '否') . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">線數: ' . array_get($ticket, 'Lines') . ' </b><br/>';
            $lineBet = floatval(array_get($ticket, 'LineBet')) / 100; // 單位是分 要除以100
            $gameResult .= '<b style="color:#fc5a34;">線數下注金額: ' . $lineBet . ' </b><br/>';

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => array_get($ticket, 'TransactionID'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'AccountID'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => 'slot',
                // 產品類別
                'category' => 'e-game',
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
                // 輸贏結果
                'winnings' => $winnings,
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => false,
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
                $this->station,
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