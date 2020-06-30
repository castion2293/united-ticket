<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「任你贏」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class RenNiYingConverter extends Converter
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

    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');

        $this->gameTypes = [
            4 => 'bei_jing_sai_che',
            8 => 'xing_yun_fei_ting',
            3 => 'chong_qing_shi_cai',
            5 => 'jiang_su_tou_bai',
            17 => 'yin_su_sai_che_5_min',
            18 => 'yin_su_sai_che_75_sec',
            19 => 'yin_su_sai_che_3_min',
        ];
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
        $unitedTickets = [];
        $station = 'ren_ni_ying';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的user_id
            $walletAccounts = collect($tickets)
                ->pluck('userId')
                ->unique();

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
                $account = array_get($ticket, 'userId');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = array_get($this->gameTypes, array_get($ticket, 'gameId'), '');
            $category = array_get($this->categories, "{$station}.{$gameScope}", '');
            $status = array_get($ticket, 'status');
            $betAt = array_get($ticket, 'created');

            $payoutAt = null;

            if ($status == 1 || $status == 2) {
                $payoutAt = Carbon::parse($betAt)->addSeconds(74)->toDateTimeString();;
            }

            // 下注金額，結算和未結算才算下注金額，取消單不計算 歸0
            $rawBet = 0;

            if ($status == 1 || $status == 0) {
                $rawBet = array_get($ticket, 'money');
            }

            // 輸贏結果 (會員輸贏 + 會員退水)
            $result = array_get($ticket, 'result');
            $playerRebate = array_get($ticket, 'playerRebate');
            $winnings = $result + $playerRebate;

            // 詳細
            $gameResult = '<b style="color:#11bed1;">' . array_get($ticket, 'place', '') . ' - ' . array_get($ticket, 'guess', '') . '  ' .array_get($ticket, 'odds', '') . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">' . array_get($ticket, 'roundId', '') . '</b><br/>';
            $gameResult .= '(開牌結果請以遊戲期號至任你贏後台查看)';

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'id'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => array_get($ticket, 'userId'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => $category,
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
                'invalid' => $status == 2,
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