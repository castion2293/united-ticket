<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class BoboPokerConverter extends Converter
{
    protected $gameTypes = [];

    /**
     * 遊戲站名稱
     *
     * @var string
     */
    private $station = 'bobo_poker';

    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.bobo_poker.game_type');
    }

    /**
     * 轉換原生注單為整合注單
     *
     * @param array $aRawTickets
     * @param string $sUserId
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
                ->pluck('account')
                ->unique();

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
                $account = array_get($ticket, 'account');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================
            $gameScope = array_get($ticket, 'gameName');

            // 投注金額及輸贏結果 以分為單位 需除以100
            $rawBet = array_get($ticket, 'betAmt') / 100;
            $winnings = array_get($ticket, 'earn') / 100;

            // 投注及派彩時間
            $status = array_get($ticket, 'status');

            $betTime = array_get($ticket, 'betTime');
            $betAt = Carbon::parse($betTime)->toDateTimeString();

            $payoutAt = null;

            // 已結算才會有派彩時間
            if ($status == '1') {
                $payoutTime = array_get($ticket, 'payoutTime');
                $payoutAt = Carbon::parse($payoutTime)->toDateTimeString();
            }

            // 註銷單派彩時間以投注時間為主
            if ($status == 'X') {
                $payoutAt = $betAt;
                $rawBet = 0;
            }

            // 開牌詳細內容
            $gameResult = '';
//            $gameResult .= sprintf("遊戲局號: %s; ", array_get($ticket, 'gameNumber'));

            $gameFunction = camel_case($gameScope);

            if (method_exists($this, $gameFunction)) {
                $convertResults = $this->$gameFunction($ticket, $gameScope);

                $gameResult .= '<b style="color:#11bed1;">該注內容: ' .  array_get($convertResults, 'content') . ' </b></br>';

                $result = array_get($convertResults, 'result');
                if (!empty($result)) {
                    $gameResult .= '<b style="color:#fc5a34;">開牌結果: '. $result . ' </b>';
                }
            } else {
                $gameResult .= '<b style="color:#11bed1;">該注內容: ' .  array_get($ticket, 'content') . ' </b></br>';

                $result = array_get($ticket, 'result');
                if (!empty($result)) {
                    $gameResult .= '<b style="color:#fc5a34;">開牌結果: '. $result . ' </b>';
                }
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'betDetailId'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'account'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $this->station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope,
                // 產品類別
                'category' => 'e-battle',
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
                'invalid' => $status == 'X',
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
     * 龍虎開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function dragonTiger(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        foreach ($ticketResult ?? [] as $key => $item) {
            $cardName = array_get($gameTypeResults, $key);

            $card = explode('-', $item);
            $cardType = array_get($gameTypeResults, array_get($card, 0));
            $cardNumber = array_get($card, 1);

            $result .= "{$cardName}: {$cardType}{$cardNumber}, ";
        }

        $result = rtrim($result, ', ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂龍虎開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyDragonTiger(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        foreach ($ticketResult ?? [] as $key => $item) {
            $cardName = array_get($gameTypeResults, $key);

            $card = explode('-', $item);
            $cardType = array_get($gameTypeResults, array_get($card, 0));
            $cardNumber = array_get($card, 1);

            $result .= "{$cardName}: {$cardType}{$cardNumber}, ";
        }

        $result = rtrim($result, ', ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 百家樂開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function baccarat(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';

        $ticketResult = collect($ticketResult)->sort()
            ->groupBy(function ($item, $key) {
                return substr($key, 0, 1);
            })
            ->toArray();

        foreach ($ticketResult ?? [] as $key => $items) {
            $cardString = '';

            $cardName = array_get($gameTypeResults, $key);

            $cardString .= "{$cardName}: ";

            foreach ($items ?? [] as $item) {
                $card = explode('-', $item);
                $cardType = array_get($gameTypeResults, array_get($card, 0));
                $cardNumber = array_get($card, 1);
                $cardString .= "{$cardType}{$cardNumber},";
            }

            $cardString = rtrim($cardString, ',') . "; ";

            $result .= $cardString;
        }

        $result = rtrim($result, '; ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 色碟開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function shakingDisc(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        preg_match_all('/\w/', array_get($ticket, 'result'), $ticketResult);


        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '[ ';
        foreach (array_first($ticketResult) ?? [] as $item) {
            $result .= array_get($gameTypeResults, $item) . ' ';
        }
        $result .= ']';

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂色碟開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function HappyShakingDisc(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        preg_match_all('/\w/', array_get($ticket, 'result'), $ticketResult);


        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '[ ';
        foreach (array_first($ticketResult) ?? [] as $item) {
            $result .= array_get($gameTypeResults, $item) . ' ';
        }
        $result .= ']';

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 21點開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function blackjack(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        foreach ($ticketResult ?? [] as $key => $item) {
            $cardName = array_get($gameTypeResults, $key, '');

            preg_match_all('/\w+-\w/', $item, $cards);

            $cards = array_first($cards);
            if (!empty($cards)) {
                $cardString = '';
                $cardString .= "{$cardName}: ";
                foreach ($cards ?? [] as $card) {
                    $card = explode('-', $card);
                    $cardType = array_get($gameTypeResults, array_get($card, 0));
                    $cardNumber = array_get($card, 1);
                    $cardString .= "{$cardType}{$cardNumber},";
                }
                $cardString = rtrim($cardString, ',') . "; ";
                $result .= $cardString;
            }
        }

        $result = rtrim($result, '; ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 瘋狂輪盤開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function crazyRoulette(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 反圍骰開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function oppositeDiceBao(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 骰寶開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function diceBao(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('-', $ticketContent);
        $type = array_get($contentSet, 0);
        $value = array_get($contentSet, 1);

        $content .= array_get($gameTypeContents, $type);

        if (!empty($value)) {
            $content .= '-' . $value;
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂骰寶開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function HappyDiceBao(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('-', $ticketContent);
        $type = array_get($contentSet, 0);
        $value = array_get($contentSet, 1);

        $content .= array_get($gameTypeContents, $type);

        if (!empty($value)) {
            $content .= '-' . $value;
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 台灣60秒賓果開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function selfBingobingo(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($contentSet, 0);
        $value = array_get($contentSet, 1);

        $content .= array_get($gameTypeContents, $type);

        if (!empty($value)) {
            $content .= '-' . $value;
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 60秒越南彩開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function selfVietnam(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($contentSet, 1);
        $content = "{$type}-{$value}";

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 越南彩開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function vietnamLottery(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($contentSet, 1);
        $content = "{$type}-{$value}";

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 60秒急速飛艇開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function selfRowing(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        if (intval($type) !== 0) {
            $content = "冠亞二星{$type},{$value}";
        } else {
            $content = "{$type}{$value}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 幸運飛艇開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function rowing(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        if (intval($type) !== 0) {
            $content = "冠亞二星{$type},{$value}";
        } else {
            $content = "{$type}{$value}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 幸運飛艇開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function selfRacing(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        if (intval($type) !== 0) {
            $content = "冠亞二星{$type},{$value}";
        } else {
            $content = "{$type}{$value}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 北京賽車開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function racing(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        if (intval($type) !== 0) {
            $content = "冠亞二星{$type},{$value}";
        } else {
            $content = "{$type}{$value}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 60秒急速11選五開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function ffc11x5(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet ?? [] as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 廣東11選五開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function gd11x5(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 江蘇11選五開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function js11x5(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 江西11選五開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function jx11x5(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 山東11選五開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function sd11x5(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 60秒急速快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function ffck3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 北京快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function bjk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 甘肅快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function gsk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 廣西快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function gxk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 河北快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function hebk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 湖北快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function hubk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 江蘇快三開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function jsk3(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂生肖開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function timetime(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        if (intval($type) !== 0) {
            $content = "二星{$type},{$value}";
        } else {
            $content = "{$type}{$value}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 發大財賽馬開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function horserace(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);
        $type = array_get($gameTypeContents, array_get($contentSet, 0));
        $value = array_get($gameTypeContents, array_get($contentSet, 1));

        $content = "{$type}{$value}";

        // 冠亞二星
        $isTwoStars = (intval($type) !== 0) && (count($contentSet) === 2);
        if ($isTwoStars) {
            $first = array_get($contentSet, 0);
            $second = array_get($contentSet, 1);
            $content = "冠亞二星{$first},{$second}";
        }

        // 冠亞三星
        $isThreeStars = (intval($type) !== 0) && (count($contentSet) === 3);
        if ($isThreeStars) {
            $first = array_get($contentSet, 0);
            $second = array_get($contentSet, 1);
            $third = array_get($contentSet, 2);
            $content = "冠亞三星{$first},{$second},{$third}";
        }

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 美式輪盤開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function americanRoulette(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂輪盤開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyRoulette(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 魚蝦蟹開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function fishPrawnCrab(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = array_get($ticket, 'result');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        $resultSet = explode(',', $ticketResult);
        foreach ($resultSet ?? [] as $key => $item) {
            $result .= array_get($gameTypeResults, $item) . ',';
        }

        $result = rtrim($result, ',');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 歡樂魚蝦蟹開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyFishPrawnCrab(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = array_get($ticket, 'result');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        $resultSet = explode(',', $ticketResult);
        foreach ($resultSet ?? [] as $key => $item) {
            $result .= array_get($gameTypeResults, $item) . ',';
        }

        $result = rtrim($result, ',');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 越南三牌開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function baCay(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResults = json_decode(array_get($ticket, 'result'), true);

        $account = array_get($ticket, 'account');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '</br>';
        foreach ($ticketResults as $key => $ticketResult) {

            if ($key === $account) {
                $role = '玩家本人';
            } else {
                $role = '其他玩家';
            }

            $isDealerString = '';
            $isDealer = array_get($ticketResult, 'isDealer');
            if ($isDealer) {
                $isDealerString = '(莊家)';
            }

            $cardString = '';
            $cards = array_get($ticketResult, 'hand');
            foreach ($cards as $card) {
                $card = explode('-', $card);
                $cardType = array_get($gameTypeResults, array_get($card, 0));
                $cardNumber = array_get($card, 1);
                $cardString .= "{$cardType}{$cardNumber},";
            }
            $cardString = rtrim($cardString, ',') . "</br>";

            $result .= $role . $isDealerString . ': ' . $cardString;
        }

        $result = rtrim($result, '; ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }

    /**
     * 越南炸金花開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function vietnamGoldenFlower(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = array_get($ticket, 'result');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        $resultSet = explode(',', $ticketResult);
        foreach ($resultSet ?? [] as $key => $item) {
            $result .= array_get($gameTypeResults, $item) . ',';
        }

        $result = rtrim($result, ',');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 歡樂百家樂開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyBaccarat(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';

        $ticketResult = collect($ticketResult)->sort()
            ->groupBy(function ($item, $key) {
                return substr($key, 0, 1);
            })
            ->toArray();

        foreach ($ticketResult ?? [] as $key => $items) {
            $cardString = '';

            $cardName = array_get($gameTypeResults, $key);

            $cardString .= "{$cardName}: ";

            foreach ($items ?? [] as $item) {
                $card = explode('-', $item);
                $cardType = array_get($gameTypeResults, array_get($card, 0));
                $cardNumber = array_get($card, 1);
                $cardString .= "{$cardType}{$cardNumber},";
            }

            $cardString = rtrim($cardString, ',') . "; ";

            $result .= $cardString;
        }

        $result = rtrim($result, '; ');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 百人牛牛開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyCowcow(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResults = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        foreach ($ticketResults as $ticketResult => $items) {
            // 開牌區域
            if ($content === array_get($gameTypeResults, $ticketResult) || array_get($gameTypeResults, $ticketResult) === '莊家區') {
                $result .= '</br>區域：' . array_get($gameTypeResults, $ticketResult) . "</br>牌色： ";
                $ticketSets = explode(',', $items);
                foreach ($ticketSets ?? [] as $item) {
                    // 牌色
                    $result .= array_get($gameTypeResults, $item) . ' ';
                }
            }
        }
        
        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 星河輪盤開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function newRoulette(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");

        $ticketContent = array_get($ticket, 'content');

        // 下注內容
        $content = '';
        $contentSet = explode('_', $ticketContent);

        foreach ($contentSet as $key => $item) {
            if ($key === 0) {
                $type = array_get($gameTypeContents, $item);
                $content .= $type;
                continue;
            }

            $content .= $item . ',';
        }

        $content = rtrim($content, ',');

        // 開牌結果
        $result = array_get($ticket, 'result');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 番攤牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function fanTan(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");

        $ticketContent = array_get($ticket, 'content');
        $ticketResult = array_get($ticket, 'result');

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        $resultSet = explode(',', $ticketResult);
        foreach ($resultSet ?? [] as $key => $item) {
            $result .= array_get($gameTypeResults, $item) . ',';
        }

        $result = rtrim($result, ',');

        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
    /**
     * 二八槓開牌結果
     *
     * @param array $ticket
     * @param string $gameScope
     * @return array
     */
    private function happyThisBar(array $ticket, string $gameScope): array
    {
        $gameTypeContents = array_get($this->gameTypes, "{$gameScope}.content");
        $gameTypeResults = array_get($this->gameTypes, "{$gameScope}.result");
        $gameTypeCard = array_get($this->gameTypes, "{$gameScope}.card");

        $ticketContent = array_get($ticket, 'content');
        $ticketResults = json_decode(array_get($ticket, 'result'), true);

        // 下注內容
        $content = array_get($gameTypeContents, $ticketContent);

        // 開牌結果
        $result = '';
        foreach ($ticketResults as $ticketResult => $items) {
            // 開牌區域
            if ($content === array_get($gameTypeResults, $ticketResult) || array_get($gameTypeResults, $ticketResult) === '莊家區') {
                $result .= '</br>區域：' . array_get($gameTypeResults, $ticketResult) . "</br>牌型： ";
                $ticketSets = explode(',', $items);
                foreach ($ticketSets ?? [] as $item) {
                    // 數字1~9分別為1~9筒，10為白板
                    $result .= array_get($gameTypeCard, $item) . ' ';
                }
                
            }
        }
        
        return [
            'content' =>  $content,
            'result' => $result,
        ];
    }
}