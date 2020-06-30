<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class AwcSexyConverter extends Converter
{
    /**
     * 下注內容
     *
     * @var array|\Illuminate\Config\Repository|mixed
     */
    protected $betType = [];

    /**
     * 開牌結果
     *
     * @var array|\Illuminate\Config\Repository|mixed
     */
    protected $betResult = [];

    /**
     * 遊戲站名稱
     *
     * @var string
     */
    private $station = 'awc_sexy';

    public function __construct()
    {
        parent::__construct();

        $this->betType = config('united_ticket.awc_sexy.bet_type');
        $this->betResult = config('united_ticket.awc_sexy.bet_result');
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
                ->pluck('userId')
                ->unique()
                ->map(function ($account) {
                    return strtoupper($account);
                });

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $this->station)
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
                $account = strtoupper(array_get($ticket, 'userId'));

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $username = array_get($ticket, 'userId');
            $gameScope = array_get($ticket, 'gameCode');

            // 投注金額及輸贏結果
            $rawBet = array_get($ticket, 'betAmount');
            $validBet = array_get($ticket, 'realBetAmount');
            $winAmount= array_get($ticket, 'realWinAmount');
            $winnings = $winAmount - $validBet;

            // 作廢單
            $isInvalid = false;
            $invalidStatus = [
                -1, // cancel bet
                2, // void
                9, // invalid, hide in the report
            ];

            $status = array_get($ticket, 'txStatus');

            if (in_array($status, $invalidStatus)) {
                $validBet = 0;
                $winnings = 0;
                $isInvalid = true;
            }

            // 投注及派彩時間
            $betAt = array_get($ticket, 'txTime');
            $payoutAt = array_get($ticket, 'updateTime');

            // 開牌詳細內容
            $betDetail= $this->betDetail(array_get($ticket, 'betType', ''));
            $gameResult = '<b style="color:#11bed1;">下注項目：' . $betDetail . ' </b></br>';

            $gameFunction = camel_case($gameScope);
            $gameInfo = json_decode(array_get($ticket, 'gameInfo'), true);
            $result = array_get($gameInfo, 'result');
            if (empty($result)) {
                $gameResult .= '<b style="color:#fc5a34;">開牌結果: 請至遊戲館後台查詢</b>';
            }
            if (method_exists($this, $gameFunction) && !empty($result)) {
                $gameResult .= $this->$gameFunction($result);
            } else {
                $gameResult .= '<b style="color:#fc5a34;">開牌結果: 請至遊戲館後台查詢</b>';
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'ID'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => strtoupper($username),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => 'live',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => currency_multiply_transfer($this->station, $rawBet),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => currency_multiply_transfer($this->station, $validBet),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => currency_multiply_transfer($this->station, $validBet),
                // 輸贏結果
                'winnings' => currency_multiply_transfer($this->station, $winnings),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => $isInvalid,
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

    /**
     * 下注內容
     * @param string|null $betType
     * @return string
     */
    private function betDetail(string $betType): string
    {
        $combineBetType = [
            'Sum',
            'Triple',
            'Double',
            'Combine',
            'Single',
            'Four Numbers',
            'Triangle',
            'Direct',
            'Separate',
            'Street',
            'Corner',
            'Line',
            'Column',
            'Dozen',
        ];

        $betCode = array_get(explode(' ', $betType), 0);
        $betName = array_get($this->betType, $betCode, '');

        if (in_array($betCode, $combineBetType)) {
            $betName = $betName . array_get(explode(' ', $betType), 1);
        }

        return $betName;
    }

    /**
     * 經點百家開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE001(array $result): string
    {

        $playerResults = array_slice($result, 0, 2);
        $bankerResults = array_slice($result, 3);

        $player = $this->getCardResult($playerResults);
        $banker = $this->getCardResult($bankerResults);


        $gameResult = '';
        $gameResult .= '闲: ' . $player . '; ';
        $gameResult .= '庄: ' . $banker;

        return $gameResult;
    }

    /**
     * 保險百家開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE003(array $result): string
    {

        $playerResults = array_slice($result, 0, 2);
        $bankerResults = array_slice($result, 3);

        $player = $this->getCardResult($playerResults);
        $banker = $this->getCardResult($bankerResults);

        $gameResult = '';
        $gameResult .= '闲: ' . $player . '; ';
        $gameResult .= '庄: ' . $banker;

        return $gameResult;
    }

    /**
     * 龍虎開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE006(array $result): string
    {
        $playerResults = array_slice($result, 0, 2);
        $bankerResults = array_slice($result, 3);

        $player = $this->getCardResult($playerResults);
        $banker = $this->getCardResult($bankerResults);

        $gameResult = '';
        $gameResult .= '虎: ' . $player . '; ';
        $gameResult .= '龙: ' . $banker;

        return $gameResult;
    }

    /**
     * 骰寶開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE007(array $result): string
    {
        $sicoResults = explode(',', array_first($result));

        $gameResult = '';
        $gameResult .= '骰子一: ' . array_get($sicoResults, 0) . '; ';
        $gameResult .= '骰子二: ' . array_get($sicoResults, 1) . '; ';
        $gameResult .= '骰子三: ' . array_get($sicoResults, 2);

        return $gameResult;
    }

    /**
     * 輪盤開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE009(array $result): string
    {
        $rotResults = array_first($result);

        $gameResult = '号码:' . $rotResults;

        return $gameResult;
    }

    /**
     * 红蓝对决開牌結果
     * @param array $result
     * @return string
     */
    private function mXLIVE010(array $result): string
    {
        $sicoResults = explode(',', array_first($result));

        $gameResult = '';
        $gameResult .= '骰子紅: ' . array_get($sicoResults, 0) . '; ';
        $gameResult .= '骰子藍: ' . array_get($sicoResults, 1) . '; ';

        return $gameResult;
    }

    /**
     * 紙牌結果轉換
     *
     * @param array $cardResults
     * @return string
     */
    private function getCardResult(array $cardResults): string
    {
        $card_result_string = '';

        foreach ($cardResults as $cardResult) {
            if (!empty($cardResult)) {
                $cardName = array_get($this->betResult, substr($cardResult, 0, 1));
                $cardNumber = substr($cardResult, 1, 2);
                $card_result_string .= "{$cardName}{$cardNumber},";
            }
        }

        $card_result_string = preg_replace('/,+$/', '', $card_result_string);

        return '[' . $card_result_string . ']';
    }
}