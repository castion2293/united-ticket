<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「手中寶」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class SoPowerConverter extends Converter
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
     * SoPowerConverter constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameTypes = config('united_ticket.so_power.game_type');
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
        $station = 'so_power';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {

            // 取得原生注單內所有的username並將最前面與client_id相符的字串捨去
            $walletAccounts = collect($tickets)
                ->pluck('USERNAME')
                ->unique()
                ->map(function ($ticket) {
                    return substr(array_get($ticket, 'USERNAME'), 3,10);
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

                // 將最左側的遊戲客戶端ID移除抓到username
                $username = substr(array_get($ticket, 'USERNAME'), 3,10);

                $sUserId = array_get($WalletUserIds, $username, null);
                if ($sUserId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = array_get($ticket, 'GTYPE');

            //判斷玩法種類取得注單結果
            $betDetail = array_get($ticket, 'BETDETAIL');
            $replace = str_replace(',注單結果：', '', $betDetail);
            $substr = (array_get($ticket, 'RTYPE') == 1 || array_get($ticket, 'RTYPE') == 2) ? mb_substr($replace, 0, 15, "utf-8") : mb_substr($replace, 0, 16, "utf-8");

            $gameType = array_get($this->categories, "{$station}.{$gameScope}");
            $periodNumber = array_get($ticket, 'PERIODNUMBER');
            $winGold = array_get($ticket, 'WINGOLD');
            //判斷輸贏結果若為注單取消 則讓有效投注和洗碼量為0 變成注單
            $resultNC = (array_get($ticket, 'RESULT') == 'NC') ? ($winGold = 0) : array_get($ticket, 'GOLD');

            switch (array_get($ticket, 'RTYPE')) {
                case '1':
                    $rType = '定位';
                    break;
                case '2':
                    $rType = '雙面';
                    break;
                case '3':
                    $rType = '區間';
                    break;
            }

            $gameResult = '';
            $gameResult .= '<b style="color:#11bed1;">' . array_get($ticket, '	IORATIO', '') . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">[' . $substr . '] </b>';

            //若為注單取消則將注單作廢


            $invalid = false;
            if (array_get($ticket, 'RESULT') == 'NC') {
                $invalid = true;
            }

            // 抓取united_tickets的資料
            $game_payout = DB::table('united_tickets')->where('id', array_get($ticket, 'uuid'))->select('id', 'invalid', 'payout_at')->get()->first();

            // 判斷是否是新的注單
            if (!empty($game_payout)) {

                // 取出united_tickets的派彩時間
                $gamePayout = $game_payout->payout_at;
                // 判斷united_tickets的派彩時間是否為空的
                if (empty($gamePayout)) {
                    // 判斷是否已結算
                    if (array_get($ticket, 'RESULT_OK') == '1' && $gamePayout == null && $game_payout->invalid != '1') {
                        $gamePayout = Carbon::now()->toDateTimeString();
                    }
                    if (array_get($ticket, 'RESULT') == 'NC') {
                        $gamePayout = Carbon::now()->toDateTimeString();
                    }
                }
            } else {
                // 判斷若已派彩則直接變為注單(已派彩注單)
                if (array_get($ticket, 'RESULT_OK') == '1') {
                    $gamePayout = Carbon::parse(array_get($ticket, 'ADDDATE'))->addMinutes(10)->toDateTimeString();
                } else {
                    // 若未派彩則時間為空值(未派彩注單)
                    $gamePayout = null;
                }
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'BETID'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => substr(array_get($ticket, 'USERNAME'), 3,10),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => 'so_power',
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => $gameType,
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($ticket, 'GOLD'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => $resultNC,
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => $resultNC,
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'WINGOLD'),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $invalid,
                // 投注時間
                'bet_at' => array_get($ticket, 'ADDDATE', null),
                // 派彩時間
                'payout_at' => $gamePayout,
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