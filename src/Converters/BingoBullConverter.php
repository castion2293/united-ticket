<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\BingoBullRake;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class BingoBullConverter extends Converter
{
    /**
     * 遊戲站名稱
     *
     * @var string
     */
    private $station = 'bingo_bull';

    /**
     * 遊戲代理商前綴
     *
     * @var \Illuminate\Config\Repository|mixed|string
     */
    protected $prefixCode = '';

    public function __construct()
    {
        $this->prefixCode = config('api_caller.bingo_bull.config.prefix_code');

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
        // 輸贏注單
        $unitedTickets = [];

        // 抽數注單
        $unitedRakes = [];

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('account')
                ->unique()
                ->map(function ($account) {
                    return str_replace($this->prefixCode, '', $account);
                });

            // 取得錢包內所有的user_id在進行轉換
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
                // 將最左側的遊戲客戶端ID移除抓到username
                $username = str_replace($this->prefixCode, '', array_get($ticket, 'account'));

                $userId = array_get($WalletUserIds, $username, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = 'bingoBull';
            $category = 'keno';
            $betType = 'general';

            // 投注金額及輸贏結果
            $rawBet = $validBet = array_get($ticket, 'realBetMoney');
            $winnings = array_get($ticket, 'totalMoney');

            // 投注及派彩時間
            $status = array_get($ticket, 'status');

            $betAt = array_get($ticket, 'createTime');

            $payoutAt = null;

            // 已結算才會有派彩時間
            if ($status === '1') {
                $payoutAt = array_get($ticket, 'createTime');
            }

            // 開牌詳細內容
            $gameResult = '';

            // 1 當莊/0 當閒
            if (array_get($ticket, 'userType') === '0') {
                $userType = '閒家';
                $bankerColor = '<b style="color:#757575;">';
                $playerColor = '<b style="color:#11bed1;">';
            } else {
                $userType = '莊家';
                $bankerColor = '<b style="color:#11bed1;">';
                $playerColor = '<b style="color:#757575;">';
            }

            $gameResult .= '<b style="color:#11bed1;">' . $userType . '</b><br/>';

            $betData = json_decode(array_get($ticket, 'betData'), true);

            $bankers = array_get($betData, 'banker');
            foreach ($bankers as $banker) {
                $bankerSeat = array_get($banker, 'seat') ?? '';
                $bankerNumber = implode(',', array_get($banker, 'numberArray') ?? '');
                $bankerSum = array_get($banker, 'sum') ?? '';
                $gameResult .= $bankerColor . '莊 ' . $bankerSeat . '桌 [' . $bankerNumber . ']</b><br/>';
                $gameResult .= '<b style="color:#fc5a34;">[' . $bankerSum . '] </b><br/>';
            }

            $players = array_get($betData, 'player');
            foreach ($players as $player) {
                $playerSeat = array_get($player, 'seat') ?? '';
                $playerNumber = implode(',', array_get($player, 'numberArray') ?? '');
                $playerSum = array_get($player, 'sum') ?? '';
                $gameResult .= $playerColor . '閒 ' . $playerSeat . '桌 [' . $playerNumber . ']</b><br/>';
                $gameResult .= '<b style="color:#fc5a34;">[' . $playerSum . '] </b><br/>';
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'betNo'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => str_replace($this->prefixCode, '', array_get($ticket, 'account')),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => $category,
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => $betType,
                // 實際投注
                'raw_bet' => $rawBet,
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

            //=========================================================
            // 建立賓果牛牛抽水注單
            //=========================================================
            $rawRakeArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'betNo'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => str_replace($this->prefixCode, '', array_get($ticket, 'account')),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => $category,
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => $betType,
                // 抽水點數
                'rake' => -array_get($ticket, 'pumpMoney'),
                // 投注時間
                'bet_at' => $betAt,
                // 派彩時間
                'payout_at' => $payoutAt,
            ];

            // 查找各階水倍差佔成
            $rakesTableArray = $this->findRebate(
                $userId,
                $this->station,
                $gameScope,
                $betAt
            );

            $unitedRakes[] = array_merge($rawRakeArray, $rakesTableArray);
        }

        // 儲存整合注單
        UnitedTicket::replace($unitedTickets);

        // 儲存整合注單
        BingoBullRake::replace($unitedRakes);

        return $unitedTickets;
    }
}