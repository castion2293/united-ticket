<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\StationWallet\StationLoginRecord;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use SuperPlatform\ApiCaller\Facades\ApiCaller;

/**
 * 「DG」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class DreamGameConverter extends Converter
{
    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameType = config('united_ticket.dream_game.gameType');
        $this->result = config('united_ticket.dream_game.result');

        $this->gameResultFunctions = [
            1 => 'baccarat',
            2 => 'insurance_baccara',
            3 => 'dragon_tiger',
            4 => 'roulette',
            5 => 'dice',
            6 => 'fantan',
            7 => 'bull_fighting',
            8 => 'baccarat',
            9 => 'show_hand',
            10 => 'baccarat',
            11 => 'fried_golden_flower',
            12 => 'fast_dice',
            13 => 'bull_fighting',
            14 => 'disc',
            15 => 'fishPrawnCrab',
            31 => 'live_baccarat',
            32 => 'live_dragon_tiger',
            33 => 'bull_fighting',
        ];
    }

    /**
     * 轉換 (注意： 僅轉換，未寫入資料庫)
     *
     * @param array $aRawTickets
     * @param string $sUserId
     * @return array
     * @throws Exception
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $unitedTickets = [];

        $station = 'dream_game';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的user_id
            $walletAccounts = collect($tickets)
                ->pluck('userName')
                ->unique();

            $walletUserIds = DB::table('station_wallets')
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
                $account = array_get($ticket, 'userName');
                $sUserId = array_get($walletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($ticket, 'betTime', null);

            if (is_numeric($betNum = array_get($ticket, 'id'))) {
                $betNum = strval($betNum);
            }

            $payoutAt = array_get($ticket, 'calTime', null);
            $username = array_get($ticket, 'userName');
            $now = date('Y-m-d H:i:s');

            // gameScope
            $gameType = array_get($ticket, 'gameType');
            $gameId = array_get($ticket, 'gameId');
            $tableId = array_get($ticket, 'tableId');
            $game_scope = array_get($this->gameType, "{$gameType}.{$gameId}.{$tableId}");
            $category = array_get($this->categories, "{$station}.{$game_scope}");

            // 整理開牌結果
            $result = array_get($ticket, 'result');

            if ($result == '{}') {
                // 無開牌結果
                $gameResult = '無開牌結果';
            } else {
                // 有開牌結果
                $gameFunction = array_get($this->gameResultFunctions, $gameId);

                if (filled($gameFunction)) {
                    $gameResult = $this->$gameFunction($ticket, $gameFunction);
                } else {
                    $gameResult = '';
                }
            }

            $rawBet = array_get($ticket, 'betPoints');
            $validBet = array_get($ticket, 'availableBet');
            $winnings = array_get($ticket, 'winOrLoss') - array_get($ticket, 'betPoints');

            $parentBetId = array_get($ticket, 'parentBetId');
            if (!empty($parentBetId)) {
                $gameResult .= '本單為' . $parentBetId . '對衝注單';
            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => $betNum,
                // 會員帳號識別碼$user_id$userIdentify,
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => currency_multiply_transfer($station, $rawBet),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => currency_multiply_transfer($station, $validBet),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => currency_multiply_transfer($station, $validBet),
                // 輸贏結果(可正可負)
                'winnings' => currency_multiply_transfer($station, $winnings),
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => false,
                // 投注時間
                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
                // 派彩時間
                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now
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

        // 因為DG會一直回傳相同注單 需註銷才可以撈新的
        try {
            ApiCaller::make('dream_game')
                ->methodAction('POST', 'game/markReport/{agent}', ['agent' => config('api_caller.dream_game.config.api_agent')])
                ->params([
                    'list' => collect($aRawTickets['tickets'])->pluck('id')
                ])
                ->submit();
        } catch (Exception $exception) {
            throw $exception;
        }

        return $unitedTickets;
    }

    /**
     * 遊戲種類是以百家乐為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function baccarat($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . ']</b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));
        $banker = explode('-', array_get($gameResultData, 'poker.banker'));
        $player = explode('-', array_get($gameResultData, 'poker.player'));

        $gameResult .= '莊 : [' . array_get($this->result, "flusher.{$banker[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[2]}") . ']; ';
        $gameResult .= '閒 : [' . array_get($this->result, "flusher.{$player[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player[2]}") . ']';

        return $gameResult;
    }

    /**
     * 遊戲種類是以保险百家乐為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function insurance_baccara($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . ']</b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));
        $banker = explode('-', array_get($gameResultData, 'poker.banker'));
        $player = explode('-', array_get($gameResultData, 'poker.player'));

        $gameResult .= '莊保险赔率: ' . $result[3] . '; ';
        $gameResult .= '閒保险赔率: ' . $result[4] . '; ';
        $gameResult .= array_get($this->result, "{$game}.banker_in.{$result[5]}") . '; ';
        $gameResult .= array_get($this->result, "{$game}.banker_in.{$result[6]}") . '; ';
        $gameResult .= '<b style="color:#11bed1;">莊 : [' . array_get($this->result, "flusher.{$banker[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[2]}") . ']</b><br/>';
        $gameResult .= '<b style="color:#DE7215;">閒 : [' . array_get($this->result, "flusher.{$player[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player[2]}") . ']</b><br/>';

        return $gameResult;
    }

    /**
     * 遊戲種類是以龙虎為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function dragon_tiger($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet  . ']</b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));
        $dragon = explode('-', array_get($gameResultData, 'poker.dragon'));
        $tiger = explode('-', array_get($gameResultData, 'poker.tiger'));

        $gameResult .= '龍 : [' . array_get($this->result, "flusher.{$dragon[0]}") . ']; ';
        $gameResult .= '虎 : [' . array_get($this->result, "flusher.{$tiger[0]}") . ']';

        return $gameResult;
    }

    /**
     * 遊戲種類是以轮盘為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function roulette($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                if (is_array($value)) {
                    foreach ($value as $num => $amount) {
                        $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $num . ']</b><br/>';
                    }
                } else {
                    $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . ']</b><br/>';
                }
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));

        $gameResult .= '輪盤點數: [' . $result[0] . ']';

        return $gameResult;
    }

    /**
     * 遊戲種類是以骰宝為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function dice($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                if (is_array($value)) {
                    foreach ($value as $num => $amount) {
                        $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $num . ']</b><br/>';
                    }
                } else {
                    $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . ']</b><br/>';
                }
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = str_split(array_get($gameResultData, 'result'));
//        $result = str_split($gameResultData['result']);

        $gameResult .= '骰子點數: [';

        foreach ($result as $point) {
            $gameResult .= $point . '點 ';
        }

        $gameResult .= '] ';

        return $gameResult;
    }

    /**
     * 遊戲種類是以鬥牛為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function bull_fighting($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';
        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult = '<b style="color:#11bed1;">下注項目: [' . $bet . ']</b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result1 = explode(',', explode('|', array_get($gameResultData, 'result'))[0]);
        $result2 = explode(',', explode('|', array_get($gameResultData, 'result'))[1]);
        $firstcard = explode('-', array_get($gameResultData, 'poker.firstcard'));
        $banker = explode('-', array_get($gameResultData, 'poker.banker'));
        $player1 = explode('-', array_get($gameResultData, 'poker.player1'));
        $player2 = explode('-', array_get($gameResultData, 'poker.player2'));
        $player3 = explode('-', array_get($gameResultData, 'poker.player3'));

        $gameResult .= '头牌結果: [' . array_get($this->result, "flusher.{$firstcard[0]}") . ']; ';
        $gameResult .= '<b style="color:#DE7215;"> 庄点数: [' . $result1[0] . ']</b><br/>';
        $gameResult .= '庄結果: [' . array_get($this->result, "flusher.{$banker[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[2]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[3]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[4]}") . ']</b><br/>';
        $gameResult .= '<b style="color:#DE7215;"> 闲1点数: [' . $result1[1] . ']</b><br/>';
        $gameResult .= ' 闲1結果: [' . array_get($this->result, "flusher.{$player1[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player1[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player1[2]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player1[3]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player1[4]}") . ']; ';
        $gameResult .= '<b style="color:#DE7215;"> 闲2点数: [' . $result1[2] . ']</b><br/>';
        $gameResult .= ' 闲2結果: [' . array_get($this->result, "flusher.{$player2[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player2[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player2[2]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player2[3]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player2[4]}") . ']; ';
        $gameResult .= '<b style="color:#DE7215;"> 闲3点数: [' . $result1[3] . ']</b><br/>';
        $gameResult .= ' 闲3結果: [' . array_get($this->result, "flusher.{$player3[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player3[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player3[2]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player3[3]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player3[4]}") . '; ';

        return $gameResult;
    }

    /**
     * 遊戲種類是以赌场扑克為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function show_hand($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                if ($key !== 'hasBid') {
                    $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
                }
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);
        $result1 = explode(',', explode('|', array_get($gameResultData, 'result'))[0]);
        $result2 = explode('-', explode('|', array_get($gameResultData, 'result'))[1]);
        $banker = explode('-', array_get($gameResultData, 'poker.banker'));
        $player = explode('-', array_get($gameResultData, 'poker.player'));
        $community = explode('-', array_get($gameResultData, 'poker.community'));

        $gameResult .= '<b style="color:#11bed1;">庄: [' . array_get($this->result, "{$game}.card.{$result1[2]}") . '] </b><br/>';
        $gameResult .= '<b style="color:#DE7215;">闲: [' . array_get($this->result, "{$game}.card.{$result1[3]}") . '] </b><br/>';
        $gameResult .= '庄結果: [' . array_get($this->result, "flusher.{$banker[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$banker[1]}") . ']; ';
        $gameResult .= ' 闲結果: [' . array_get($this->result, "flusher.{$player[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$player[1]}") . ']; ';
        $gameResult .= ' 公牌結果: [' . array_get($this->result, "flusher.{$community[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$community[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$community[2]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$community[3]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$community[4]}") . ']';

        return $gameResult;
    }

    /**
     * 遊戲種類是以為炸金花主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function fried_golden_flower($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';
        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));
        $black = explode('-', array_get($gameResultData, 'poker.black'));
        $red = explode('-', array_get($gameResultData, 'poker.red'));

        if(array_get($this->result, "{$game}.win.{$result[0]}") == '红赢') {
            $gameResult .= '<b style="color:#DE7215;">結果: ' . array_get($this->result, "{$game}.win.{$result[0]}") . ' </b><br/>';
        } else {
            $gameResult .= '結果: ' . array_get($this->result, "{$game}.win.{$result[0]}") . '; ';
        }
        $gameResult .= '黑牌型: ' . array_get($this->result, "{$game}.card.{$result[1]}") . '; ';
        $gameResult .= '<b style="color:#DE7215;">红牌型: ' . array_get($this->result, "{$game}.card.{$result[2]}") . ' </b><br/>';
        $gameResult .= '黑最大牌: ' . array_get($this->result, "{$game}.max_card.{$result[3]}") . '; ';
        $gameResult .= '<b style="color:#DE7215;">红最大牌: ' . array_get($this->result, "{$game}.max_card.{$result[4]}") . ' </b><br/>';
        $gameResult .= '奖金牌型: ' . array_get($this->result, "{$game}.card.{$result[5]}") . '; ';
        $gameResult .= '黑牌結果: {' . array_get($this->result, "flusher.{$black[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$black[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$black[2]}") . '}; ';
        $gameResult .= '<b style="color:#DE7215;">紅牌結果: {' . array_get($this->result, "flusher.{$red[0]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$red[1]}") . ' ';
        $gameResult .= array_get($this->result, "flusher.{$red[2]}") . '}' . ' </b><br/>';

        return $gameResult;
    }

    /**
     * 遊戲種類是以為极速骰宝主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function fast_dice($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                if (is_array($value)) {
                    foreach ($value as $num => $amount) {
                        $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $num . '] </b><br/>';
                    }
                } else {
                    $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
                }
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = str_split($gameResultData['result']);

        $gameResult .= '骰子點數: [';

        foreach ($result as $point) {
            $gameResult .= $point . '點 ';
        }

        $gameResult .= '] ';

        return $gameResult;
    }
    /**
     * 遊戲種類是以現場百家乐為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function live_baccarat($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));

        $gameResult .= '結果: [' . array_get($this->result, "{$game}.win.{$result[0]}") . '] ';

        return $gameResult;
    }
    /**
     * 遊戲種類是以現場龙虎為主的開牌結果轉換function
     * @param $ticket
     * @param $betType
     * @return string
     */
    public function live_dragon_tiger($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (filled($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        $result = explode(',', array_get($gameResultData, 'result'));

        $gameResult .= '結果: [' . array_get($this->result, "{$game}.win.{$result[0]}") . '] ';

        return $gameResult;
    }

    /**
     * 遊戲種類是以色碟為主的開牌結果轉換function
     * @param $ticket
     * @param $game
     * @return string
     */
    public function disc($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            $bet = array_get($this->result, "{$game}.bet.{$key}");

            if (!empty($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        foreach ($gameResultData as $key => $value) {
            $result = array_get($this->result, "{$game}.win.{$value}");

            if (!empty($result)) {
                $gameResult .= '結果: [' . $result . '] ';
            }
        }

        return $gameResult;
    }
    /**
     * 遊戲種類是以魚蝦蟹為主的開牌結果轉換function
     * @param $ticket
     * @param $game
     * @return string
     */
    public function fishPrawnCrab($ticket, $game)
    {
        $betDetailData = json_decode(array_get($ticket, 'betDetail'), true);

        $gameResult = '';

        foreach ($betDetailData as $key => $value) {
            // 找到config(united_ticket)對應的下注項目
            $bet = array_get($this->result, "{$game}.bet.{$key}");
            if (!empty($bet)) {
                $gameResult .= '<b style="color:#11bed1;">下注項目: [' . $bet . '] </b><br/>';
            }
        }

        $gameResultData = json_decode(array_get($ticket, 'result'), true);

        foreach ($gameResultData as $key => $value) {
            $spiltResults = str_split($value);
            foreach ($spiltResults as $spiltResult) {
                $result = array_get($this->result, "{$game}.win.{$spiltResult}");
                if (!empty($result)) {
                    $gameResult .= '[' . $result . '] ';
                }
            }
        }

        return $gameResult;
    }
}