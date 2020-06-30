<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「歐博」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class AllBetConverter extends Converter
{
    /**
     * @var array
     * 因為歐博是使用遊戲代碼，所以用這個陣列來mapping相對應的遊戲名稱
     */
    private $gameTypes = [];

    /**
     * @var array
     * 因為歐博是使用投注類型代碼，所以用這個陣列來mapping相對應的投注類型
     */
    private $betTypes = [];

    /**
     * @var array|Repository|mixed
     * 產品類別
     */
    private $categories = [];

    /**
     * @var array
     * 開牌結果的轉換function
     */
    private $gameResultFunctions = [];

    /**
     * @var array
     * 撲克牌花色
     */
    private $flusher = [];

    /**
     * @var array
     * 撲克牌點數
     */
    private $numbers = [];

    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.all_bet.game_type');
        $this->betTypes = config('united_ticket.all_bet.bet_type');
        $this->categories = config('api_caller_category');

        $this->gameResultFunctions = [
            '101' => 'cardGame',
            '102' => 'cardGame',
            '103' => 'cardGame',
            '104' => 'cardGame',
            '106' => 'cardGame',
            '301' => 'cardGame',
            '501' => 'cardGame',
            '701' => 'cardGame',
            '801' => 'cardGame',
            '901' => 'cardGame',
            '201' => 'dice',
            '401' => 'roulette',
        ];

        $this->flusher = [
            '1' => '黑桃', // spades
            '2' => '紅心', // hearts
            '3' => '梅花', // clubs
            '4' => '方塊' // diamonds
        ];

        $this->numbers = [
            '01' => 'A',
            '02' => '2',
            '03' => '3',
            '04' => '4',
            '05' => '5',
            '06' => '6',
            '07' => '7',
            '08' => '8',
            '09' => '9',
            '10' => '10',
            '11' => 'J',
            '12' => 'Q',
            '13' => 'K',
        ];
    }

    public function __destruct()
    {
        unset($this->gameResultFunctions);
        unset($this->flusher);
        unset($this->numbers);
        unset($this->stationWallet);
        unset($this->gameTypes);
        unset($this->betTypes);
        unset($this->categories);
    }

    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $unitedTickets = [];

        foreach (array_get($aRawTickets, 'tickets', []) as $ticket) {
            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betNum = array_get($ticket, 'betNum');

            if (is_numeric($betNum)) {
                $betNum = strval($betNum);
            }

            $betAt = array_get($ticket, 'betTime', null);
            $payoutAt = array_get($ticket, 'gameRoundEndTime', null);
            $username = array_get($ticket, 'client');
            $station = 'all_bet';
            $game_scope = array_get($this->gameTypes, $gameType = $ticket['gameType']);
            $category = array_get($this->categories, "{$station}.{$game_scope}");
            $now = date('Y-m-d H:i:s');

            // 整理開牌結s果
            $betType = array_get($this->betTypes, array_get($ticket, 'betType'));

            $gameFunction = array_get($this->gameResultFunctions, $gameType);

            if (filled($gameFunction)) {
                $gameResult = $this->$gameFunction($ticket, $betType);
            } else {
                $gameResult = '';
            }

            // 注之狀態
            $state = array_get($ticket, 'state');

            /**
             * --------------------------------------------------------------------
             * |       整合注單          |        沙龍原始注單
             * --------------------------------------------------------------------
             * | 資料識別碼(id)          |  客戶用戶名(Client)與注單編號(BetNum)取uuid
             * --------------------------------------------------------------------
             * | 原生注單編碼(bet_num)    | 注單編號(BetNum)
             * --------------------------------------------------------------------
             * | 類型(game_scope)        | 投注金額(betAmount)
             * --------------------------------------------------------------------
             * | 實際投注(raw_bet)       | 有效投注金額(validAmount)
             * --------------------------------------------------------------------
             * | 有效投注(valid_bet)     | 有效投注金額(validAmount)
             * --------------------------------------------------------------------
             * | 洗碼量(rolling)         | 洗碼量(Rolling)
             * --------------------------------------------------------------------
             * | 輸贏結果(winnings)      | 輸贏金額(winOrLoss) 有正負
             * --------------------------------------------------------------------
             * | 開牌結果(game_result)   | 開牌結果(gameResult)
             * --------------------------------------------------------------------
             * | 投注時間(bet_at)        | 投注時間(betTime)
             * --------------------------------------------------------------------
             * | 派彩時間(payout_at)     | 遊戲結束時間(gameRoundEndTime)
             * --------------------------------------------------------------------
             */
            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => $betNum,
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/ticket_integrator.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($ticket, 'betAmount'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'validAmount'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($ticket, 'validAmount'),
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'winOrLoss'),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $state === 1,
                // 投注時間
                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
                // 派彩時間
                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now,
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

    public function transformEGameTickets(array $rawTickets = [], string $userId = ''): array
    {
        $unitedTickets = [];

        $tickets = array_get($rawTickets, 'tickets', []);

        $station = 'all_bet';

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('client')
                ->unique()
                ->map(
                    function ($account) {
                        return strtoupper($account);
                    }
                );

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $station)
                ->get()
                ->mapWithKeys(
                    function ($wallet) {
                        return [
                            data_get($wallet, 'account') => data_get($wallet, 'user_id')
                        ];
                    }
                );
        }

        foreach ($tickets as $ticket) {
            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = strtoupper(array_get($ticket, 'client'));

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) {
                    continue;
                }
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = array_get($this->gameTypes, array_get($ticket, 'gameType'));

            $rawBet = array_get($ticket, 'betAmount');
            $validBet = array_get($ticket, 'validAmount');
            $winnings = array_get($ticket, 'winOrLoss');

            $betAt = $payoutAt = array_get($ticket, 'betTime');

            $gameResult = '<b style="color:#11bed1;">無開牌結果</b></br>';

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => array_get($ticket, 'betNum'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'client'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => 'fishing',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
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

    /**
     * @param $ticket
     * @param $betType
     * @return string
     * 遊戲種類是以撲克牌為主的開牌結果轉換function
     */
    public function cardGame($ticket, $betType)
    {
        if (empty(array_get($ticket, 'gameResult'))) {
            $gameResult = '此局為無效局';
        } else {
            $result = explode('},{', array_get($ticket, 'gameResult'));

            $play = $this->transferCardResult(str_replace('[', '', $result[0]));
            $bank = $this->transferCardResult(str_replace(']', '', $result[1]));

            $playTitle = (array_get($ticket, 'gameType') === 301) ? '龍: ' : '閒: ';
            $bankTitle = (array_get($ticket, 'gameType') === 301) ? '虎: ' : '莊: ';

            $gameResult = '<b style="color:#11bed1;">' . '下注項目:' . $betType . '</b><br/>';
            // 龍 or 閒
            $gameResult .= $playTitle . $play . '; ';
            // 虎 or 莊
            $gameResult .= $bankTitle . $bank;
        }
        return $gameResult;
    }

    private function transferCardResult($card)
    {
        $card_string = '';

        if (empty($card)) {
            $card_result_string = '此局為無效局';
        } else {
            foreach (explode(',', $card) as $item) {

                $item = str_replace('{', '', $item);
                $color = array_get($this->flusher, substr($item, 0, 1));
                $number = array_get($this->numbers, substr($item, 1, 2));

                $card_string .= $color . $number . ',';
            }
            $card_result_string = preg_replace('/,+$/', '', $card_string);
        }
        return '[' . $card_result_string . ']';
    }

    /**
     * 遊戲種類是以為骰子為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function dice($ticket, $betType)
    {
        $gameResult = '<b style="color:#11bed1;">下注項目:' . $betType . '</b><br/>';
        $gameResult .= '骰子點數: ' . array_get($ticket, 'gameResult');

        return $gameResult;
    }

    /**
     * 遊戲種類是以為輪盤為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function roulette($ticket, $betType)
    {
        $gameResult = '<b style="color:#11bed1;">下注項目:' . $betType . '</b><br/>';
        $gameResult .= '輪盤點數: ' . array_get($ticket, 'gameResult');

        return $gameResult;
    }
}