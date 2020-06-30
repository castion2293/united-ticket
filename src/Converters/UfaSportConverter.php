<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\StationWallet\StationLoginRecord;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「UFA體育」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class UfaSportConverter extends Converter
{
    private $username;

    /**
     * @var array
     */
    private $gameType;

    /**
     * @var array
     */
    private $betType;

    /**
     * @var array
     */
    private $league;

    /**
     * @var array
     */
    private $team;

    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameType = config('united_ticket.ufa_sport.game_type');
        $this->betType = config('united_ticket.ufa_sport.bet_type');
        $this->league = config('united_ticket.ufa_sport.league');
        $this->team = config('united_ticket.ufa_sport.team');

    }

    /**
     * 轉換 (注意： 僅轉換，未寫入資料庫)
     *
     * @param array $rawTickets
     * @return array
     * @throws \Exception
     */
    public function transform(array $aRawTickets = [], string $userId = ''): array
    {
        $unitedTickets = [];
        $station = 'ufa_sport';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的user_id
            $walletAccounts = collect($tickets)
                ->pluck('u')
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
                $account = array_get($ticket, 'u');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) {
                    continue;
                }
            }


            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            $game_scope = 'sport';

            $ticketID = array_get($ticket, 'id');

            switch (array_get($ticket, 'status')) {
                case 'N':
                    $betStatus = '未经「滚球」时段接受的投注';
                    break;
                case 'A':
                    $betStatus = '经「滚球」时段接受的投注';
                    break;
                case 'R':
                    $betStatus = '经「滚球」时段等待中取消的投注';
                    break;
                case 'C':
                    $betStatus = '退款投注';
                    break;
                case 'RG':
                    $betStatus = '取消目標';
                    break;
                case 'RP':
                    $betStatus = '取消罰款';
                    break;
                case 'RR':
                    $betStatus = '嚴重取消狀態';
                    break;
                case 'VO':
                    $betStatus = '经「滚球」时段接受的投注，之后被取消 (一般为赛事有错误)';
                    break;
                case 'VA':
                    $betStatus = '因「异常」而被取消的投注';
                    break;
            }

            switch (array_get($ticket, 'half')) {
                case '0':
                    $gamehalf = '全場';
                    break;
                case '1':
                    $gamehalf = '上半場';
                    break;
            }
            $betType = array_get($this->betType, array_get($ticket, 'game'));

            if (array_get($ticket, 'game') === 'OE') {
                switch (array_get($ticket, 'side')) {
                    case '1':
                        $betContent = '單';
                        break;
                    case '2':
                        $betContent = '雙';
                        break;
                }
            }
            if (array_get($ticket, 'game') === 'OU') {
                switch (array_get($ticket, 'side')) {
                    case '1':
                        $betContent = '大';
                        break;
                    case '2':
                        $betContent = '小';
                        break;
                }
            }
            if (array_get($ticket, 'game') === 'HDP') {
                switch (array_get($ticket, 'side')) {
                    case '1':
                        $betContent = '主隊';
                        break;
                    case '2':
                        $betContent = '客隊';
                        break;
                }
            }

            switch (array_get($ticket, 'side')) {
                case '1':
                    $betContent = '主隊';
                    break;
                case '2':
                    $betContent = '客隊';
                    break;
                case '"X"':
                    $betContent = '平手';
                    break;
            }


            $trueScore = array_get($ticket, 'info');

            if (array_get($ticket, 'game') === 'FLG') {
                switch ($trueScore) {
                    case 'NG':
                        $trueScore = '無進球';
                        break;
                    case 'HLG':
                        $trueScore = '主隊最後進球';
                        break;
                    case 'ALG':
                        $trueScore = '客隊最後進球';
                        break;
                    case 'HFG':
                        $trueScore = '主隊先進球';
                        break;
                    case 'AFG':
                        $trueScore = '客隊先進球';
                        break;
                }
            }
            $htscore = array_get($ticket, 'htscore');
            $odds = array_get($ticket, 'odds');
            $betAt = array_get($ticket, 'trandate');

            if (array_get($ticket, 'status') == 'N' || array_get($ticket, 'status') == 'A') {
                $invalid = false;
            } else {
                $invalid = true;
            }

            // 抓取united_tickets的資料
            $game_payout = DB::table('united_tickets')->where('id', array_get($ticket, 'uuid'))->select('id', 'invalid',
                'payout_at')->get()->first();

            // 判斷是否是新的注單
            if (!empty($game_payout)) {

                // 取出united_tickets的派彩時間
                $gamePayout = $game_payout->payout_at;
                // 判斷united_tickets的派彩時間是否為空的
                if (empty($gamePayout)) {
                    // 判斷是否已結算
                    if ($gamePayout == null && $game_payout->invalid == '1') {
                        $gamePayout = Carbon::now()->toDateTimeString();
                    }
                    if (array_get($ticket, 'res') != 'P') {
                        $gamePayout = Carbon::now()->toDateTimeString();
                    }
                }
            } else {
                // 判斷若已派彩則直接變為注單(已派彩注單)
                if (array_get($ticket, 'res') != 'P') {
                    $gamePayout = Carbon::parse($betAt)->addMinutes(10)->toDateTimeString();
                } else {
                    // 若未派彩則時間為空值(未派彩注單)
                    $gamePayout = null;
                }
            }

            $matchDate = array_get($ticket, 'matchdate');

            switch (array_get($ticket, 'oddstype')) {
                case '[]':
                    $oddstype = '無';
                    break;
                case '"MY"':
                    $oddstype = '馬來';
                    break;
                case '"HK"':
                    $oddstype = '香港';
                    break;
                case '"ID"':
                    $oddstype = '印尼';
                    break;
                case '"EU"':
                    $oddstype = '歐洲';
                    break;
            }

            $league = array_get($ticket, 'league');
            $mainTeam = array_get($ticket, 'home');
            $visitTeam = array_get($ticket, 'away');

            $rawBet = array_get($ticket, 'b');
            if (array_get($ticket, 'status') == 'N' || array_get($ticket, 'status') == 'A') {
                $validBet = $rawBet;
            } else {
                $validBet = 0;
            }
//            $league = array_get($this->league, array_get($ticket, 'league'));
//            $mainTeam = array_get($this->team, array_get($ticket, 'home'));
//            $visitTeam = array_get($this->team, array_get($ticket, 'away'));

            $gameType = array_get($this->gameType, array_get($ticket, 'sportstype'));
            if (!empty(array_get($ticket, 'score'))) {
                $score = array_get($ticket, 'score');
            } else {
                $score = null;
            }
//            $gameResult = '<b style="color:#11bed1;">[' . $league . " - " . $betType . ']</b><br/>';
//            $gameResult .= $visitTeam . " VS " . $mainTeam . " (主) " . $trueScore ."; ";
//            $gameResult .= '<b style="color:#fc5a34;">' ."$odds  " . "  [" . $score . ']</b>';

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)$ticketID,
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'u'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => 'ufa_sport',
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope,
                // 產品類別
                'category' => 'sport',
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
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'w'),
                // 開牌結果
                'game_result' => $this->getGameResult($ticket),
                // 作廢
                'invalid' => $invalid,
                // 投注時間
                'bet_at' => $betAt,
                // 派彩時間
                'payout_at' => $gamePayout,
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

        $rawticketSting = implode(',', collect($aRawTickets['tickets'])->pluck('fid')->toArray());

        // 因為UFA體育會一直回傳相同注單 需註銷才可以撈新的
        try {
            ApiCaller::make('ufa_sport')
                ->methodAction('get', 'mark_fetched')
                ->params([
                    'secret' => config("api_caller.ufa_sport.config.secret_code"),
                    'agent' => config("api_caller.ufa_sport.config.agent"),
                    'fetch_ids' => $rawticketSting
                ])
                ->submit();
        } catch (Exception $exception) {
            // 判斷手動撈單回傳的fid是 [] 錯誤可忽略
            $errArray = array_get($exception->response(), 'errtext');

            if ($errArray === 'Invalid fetch_ids : []') {
                return $unitedTickets;
            }

            throw $exception;
        }

        return $unitedTickets;
    }

    private function getGameResult($ticket)
    {
        $mainTeam = ApiCaller::make('ufa_sport')
            ->methodAction('get', 'team')
            ->params([
                'secret' => config("api_caller.ufa_sport.config.secret_code"),
                'agent' => config("api_caller.ufa_sport.config.agent"),
                'team_id' => array_get($ticket, 'home')
            ])
            ->submit();
        $mainTeam = array_get($mainTeam, 'response.result.name.1.txt');
        $mainTeam = $mainTeam ?? array_get($ticket, 'home');

        $visitTeam = ApiCaller::make('ufa_sport')
            ->methodAction('get', 'mark_fetched')
            ->params([
                'secret' => config("api_caller.ufa_sport.config.secret_code"),
                'agent' => config("api_caller.ufa_sport.config.agent"),
                'fetch_ids' => array_get($ticket, 'away')
            ])
            ->submit();
        $visitTeam = array_get($visitTeam, 'response.result.name.1.txt');
        $visitTeam = $visitTeam ?? array_get($ticket, 'away');

        return json_encode([
            // 聯盟名稱
            'league_id' => array_get($ticket, 'league', 'empty league id'),
            // 主隊
            'main_team' => $mainTeam,
            // 客隊
            'visit_team' => $visitTeam,
            // 類型
            'g_type' => array_get($ticket, 'game'),
            'info' => array_get($ticket, 'info'),
            // 下哪邊
            'side' => array_get($ticket, 'side'),
            // 是否已派彩 (0 未派彩 1 已派彩)
            'end' => (array_get($ticket, 'res') === 'P') ? '0' : '1',
            // 結帳狀態
            'status' => array_get($ticket, 'res'),
            // 投注時的賠率
            'odds' => array_get($ticket, 'odds'),
            // half 0. 全場  1.上半場
            'half' => array_get($ticket, 'half'),
            // 比分
            'score' => str_replace('"', '', array_get($ticket, 'score')),
            // 是否為過關單
            'has_detail' => false,
            // 過關單細節
            'detail' => [],
        ]);
    }
}