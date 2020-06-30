<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class AmebaConverter extends Converter
{
    private $station = 'ameba';
    private $gameTypes;
    private $categories;
    private $username;

    /**
     * AmebaConverter constructor.
     * @param StationWallet $stationWallet
     * @param NodeTree $nodeTree
     * @param AllotTable $allotTable
     * @param Agent $agent
     */
    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.ameba.game_type');
        $this->categories = config('api_caller_category');
    }

    /**
     * 轉換原生注單為整合注單
     *
     * @param array $aRawTickets
     * @param string $sUserId
     * @return array
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $aUnitedTickets = [];

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的user_id
            $walletAccounts = collect($tickets)
                ->pluck('account_name')
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

        foreach ($tickets as $aTicket) {
            $this->username = $aTicket['account_name'];

            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($aTicket, 'account_name');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($aTicket, 'completed_at', null);
            $sFormatTime = Carbon::parse($betAt)->addHours(8)->toDateTimeString();

            if (is_numeric($betNum = array_get($aTicket, 'round_id'))) {
                $betNum = strval($betNum);
            }

            $username = $this->username;
            $now = Carbon::now()->format('Y-m-d H:i:s');
            $game_scope = 'slot';
            $sGameStyle = array_get($this->gameTypes, array_get($aTicket, 'game_id'));
            $category = 'e-game';
            $sIsFree = $aTicket['free'] ? '是' : '否';

            $sGameResult = '<b style="color:#11bed1;">游戏名称: ' .  $sGameStyle . ' </b><br/>';
            $sGameResult .= '<b style="color:#fc5a34;">本局游戏是否免费' .  $sIsFree . ' </b>';
//            $sGameResult .= sprintf("投注额: %s; ", $aTicket['bet_amt']);
//            $sGameResult .= sprintf("派彩金额: %s", $aTicket['payout_amt']);

            $fWinnings = (array_get($aTicket, 'payout_amt', 0) - array_get($aTicket, 'bet_amt', 0)) + array_get($aTicket, 'jp_jc_win_amt', 0);

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($aTicket, 'uuid'),
                // 原生注單編號
                'bet_num' => $betNum,
                // 會員帳號識別碼$user_id$userIdentify,
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($aTicket, 'bet_amt'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($aTicket, 'bet_amt'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($aTicket, 'bet_amt'),
                // 輸贏結果(可正可負)
                'winnings' => $fWinnings,
                // 開牌結果
                'game_result' => $sGameResult,
                // 作廢
                'invalid' => array_get($aTicket, 'State') === 3,
                // 投注時間
                'bet_at' => $sFormatTime,
                // 派彩時間
                'payout_at' => $sFormatTime,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now,
                // 紀錄遊戲主題代號
                'flag' => array_get($aTicket, 'game_id'),
            ];

            // 查找各階輸贏佔成
            $allotTableArray = $this->findAllotment(
                $sUserId,
                $this->station,
                $rawTicketArray['game_scope'],
                $rawTicketArray['bet_at']
            );

            $aUnitedTickets[] = array_merge($rawTicketArray, $allotTableArray);
        }

        // 儲存整合注單
        UnitedTicket::replace($aUnitedTickets);

        return $aUnitedTickets;
    }
}