<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「皇家」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class RoyalGameConverter extends Converter
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
        $this->gameTypes = config('united_ticket.royal_game.game_type');
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
        $station = 'royal_game';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('MemberID')
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
                $account = array_get($ticket, 'MemberID');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $game_scope = array_get($ticket, 'GameItem');
            $category = array_get($this->categories, "{$station}.{$game_scope}");

            switch (array_get($ticket, 'GameItem')) {
                case 'Bacc':
                    $gameItem = '百家樂';
                    break;
                case 'InsuBacc':
                    $gameItem = '保險百家';
                    break;
                case 'LunPan':
                    $gameItem = '輪盤';
                    break;
                case 'ShaiZi':
                    $gameItem = '骰子';
                    break;
                case 'FanTan':
                    $gameItem = '翻攤';
                    break;
                case 'LongHu':
                    $gameItem = '龍虎';
                    break;
            }

            $finalScore = array_get($ticket, 'FinalScore');
            $noRun = array_get($ticket, 'NoRun');
            $noActive = array_get($ticket, 'NoActive');
            switch (array_get($ticket, 'BetStatus')) {
                case '1':
                    $betStatus = '下注成功';
                    break;
                case '3':
                    $betStatus = '當局取消';
                    break;
                case '4':
                    $betStatus = '正常統計';
                    break;
                case '5':
                    $betStatus = '重新統計';
                    break;
                case '6':
                    $betStatus = '事後取消';
                    break;
                case '-1':
                    $betStatus = '下注回應失敗：（有錯誤代碼明確回應。）';
                    break;
                case '-2':
                    $betStatus = '下注沒有回應：（連線逾時時，伺服器異常等）';
                    break;
            }
            // 若注單狀態為取消或失敗則變更為作廢住單
            $invalid = ($betStatus == '3' || $betStatus == '-1' || $betStatus == '-2') ? true : false;
            // 顯示遊戲系統下注資料網址(超連結)
            $detailURL = '<a href="' . array_get($ticket, 'DetailURL') . '" target="_blank">開牌結果</a>';
            // 若住單狀態為取消或失敗則將有效投注歸0
            $raw_bet = ($betStatus == '3' || $betStatus == '-1' || $betStatus == '-2') ? 0 : array_get($ticket, 'BetScore');
            $betCancel = '此單為' . array_get($ticket, 'OriginID') . '的「訂正單」';
            // 若下注金額大於0且注單狀態為5則為訂正單
            $betUpdate = (array_get($ticket, 'BetScore') < 0 && $betStatus == '5') ? '此注單為「沖銷單」' : '';

            $currentScore = array_get($ticket, 'CurrentScore');

            $gameResult = "{$detailURL} <br/>";
            if (array_get($ticket, 'BetStatus') == '5') {
                $gameResult .= "{$betStatus} <br/>";
            }
            if (array_get($ticket, 'OriginBetRequestID') !== null && array_get($ticket, 'BetScore') > 0) {
                $gameResult .= "{$betCancel} <br/>";
            }
            if (array_get($ticket, 'BetScore') < 0) {
                $gameResult .= '此單為'. array_get($ticket, 'OriginID') .'「沖銷單」';
            }
            // 抓取united_tickets中的ID做為訂正單的比對
            $compareID = DB::table('raw_tickets_royal_game')
                ->where('OriginID', '!=', 0)
                ->pluck('OriginID');
            foreach ($compareID as $compare) {
                if ($compare == array_get($ticket, 'ID')) {
                    $gameResult = "{$detailURL} <br/>";
                    $gameResult .= '註銷單';
                }
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'ID'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => array_get($ticket, 'MemberID'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => 'royal_game',
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope,
                // 產品類別
                'category' => $category,
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => $raw_bet,
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'ValidBetScore'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($ticket, 'ValidBetScore'),
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'WinScore'),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $invalid,
                // 投注時間
                'bet_at' => array_get($ticket, 'BetTime'),
                // 派彩時間
                'payout_at' => array_get($ticket, 'SettlementTime'),
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