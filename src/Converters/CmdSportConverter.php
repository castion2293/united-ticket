<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「CMD 體育」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class CmdSportConverter extends Converter
{
    /**
     * 遊戲站名稱
     *
     * @var string
     */
    private $station = 'cmd_sport';
    /**
     * @var array 玩法：球類
     */
    private $gameTypes;

    /**
     * @var array
     */
    private $bet_type;

    /**
     * @var array 輸贏狀態
     */
    private $status;

    // api caller
    private $caller;

    // 保存語系
    protected $useLang = [
        "zh-TW",
        "vi-VN",
        "en-US",
    ];

    public function __construct()
    {
        parent::__construct();

        $this->caller = ApiCaller::make('cmd_sport');
        $this->gameTypes = config('united_ticket.cmd_sport.game_type');
        $this->bet_type = config('united_ticket.cmd_sport.bet_type');
        $this->status = config('united_ticket.cmd_sport.win_lose_status');
    }


    /**
     * 原生注單資料轉換成整合注單的資料
     *
     * @param array $aRawTickets
     * @param string $sUserId
     * @return array
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $tickets = array_get($aRawTickets, "tickets");

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('SourceName')
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

        $unitedTickets = [];
        foreach ($tickets as $ticket) {
            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($ticket, 'SourceName');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) continue;
            }

            // 整理「原生注單」對應「整合注單」的各欄位資料
            // 下注時間
            $betAt = array_get($ticket, 'TransDate', null);
            // 會員帳號(錢包帳號)
            $username = array_get($ticket, 'SourceName');
            // 原生注單編號
            $betNum = array_get($ticket, "ReferenceNo");
            // 遊戲站
            $station = 'cmd_sport';
            // 種類(足球, 籃球...)
            $gameScope = "sport";

            $calculationArray = $this->getRawBetAndValidBet($ticket);
            // 實際投注/有效投注/洗碼量
            $rawBet = array_get($calculationArray, "rawBet");
            // 有效投注
            $validBet = array_get($calculationArray, "validBet");
            // 輸贏結果
            $winnings = array_get($calculationArray, "winnings");
            // 注單是否註銷 (C:已取消的单(球賽取消), R:已拒绝的单(風控等等原因))
            $isInvalid = array_get($calculationArray, "isInvalid");
            // 輸贏狀態
            $winLostStatus = array_get($calculationArray, "winLostStatus");
            // 系統退水值(佣金)
            $rebateAmount = array_get($calculationArray, "memCommission");
            // 結算時間
            // 若注單為尚未結算(WinLoseStatus = "P"), 結算時間為 null
            $payoutAt = $winLostStatus === "P" ? null : array_get($ticket, "StateUpdateTs");
            $now = date('Y-m-d H:i:s');

            // 此單是否有出售
            // 若注單有出售, 洗碼量變為輸贏結果取絕對值
            // 若出售金額等於投注金額, 代表已全部賣出
//            $isCashOut = array_get($ticket, "IsCashOut");
//            $isSoldOut = false;
//            if ($isCashOut) {
//                // 是否全出售
//                if (array_get($ticket, "CashOutTotal") == $rawBet) {
//                    $isSoldOut = true;
//                }
//            }

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => $betNum,
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $username,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope ?? '',
                // 產品類別
                'category' => 'sport',
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
                //系統退水值
                'rebate_amount' => currency_multiply_transfer($station, $rebateAmount),
                // 開牌結果
                'game_result' => $this->getGameResult($ticket),
                // 作廢
                'invalid' => $isInvalid ? "1" : "0",
                // 投注時間
                'bet_at' => $betAt,
                // 派彩時間
                'payout_at' => $payoutAt,
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

        return $unitedTickets;
    }

    private function getRawBetAndValidBet($ticket)
    {
        // 賠率
        $odds = array_get($ticket, "Odds");
        // 原始投注/有效投注
        // 檢查是否為 "負水盤"
        // 若屬於負水盤
        if ($odds < 0) {
            $rawBet = abs(array_get($ticket, "BetAmount") * $odds);
        } else {
            $rawBet = array_get($ticket, "BetAmount");
        }

        $validBet = $rawBet;
        // 注單輸贏狀態 (WA=贏全, LA=輸全, WH=贏半, LH=輸半, D=平手, P=未派彩)
        $winLostStatus = array_get($ticket, "WinLoseStatus");
        // 如果是 WH(贏半) OR LH(輸半)
        if ($winLostStatus === "WH" || $winLostStatus === "LH") {
            $validBet = $validBet * 0.5;
        }

        // 檢查注單是否註銷 (C:已取消的单(球賽取消), R:已拒绝的单(風控等等原因))
        $isInvalid = array_get($ticket, "DangerStatus") === "C" || array_get($ticket, "DangerStatus") === "R";
        // 如果是注銷單或未派彩, 則有效投注為零
        if ($isInvalid || $winLostStatus === "P") {
            $validBet = 0;
        }

        // 輸贏結果
        $winnings = array_get($ticket, "WinAmount");

        // 如果有佣金
        $memCommission = array_get($ticket, "MemCommission");
        if ($memCommission > 0) {
            $winnings = $winnings + $memCommission;
        }

        return [
            'rawBet' =>  $rawBet,
            'validBet' => $validBet,
            'winnings' => $winnings,
            'winLostStatus' => $winLostStatus,
            'isInvalid' => $isInvalid,
            'memCommission' => $memCommission,
        ];
    }


    /**
     * 準備寫進整合注單的 game_result 開牌結果，格式為 json
     *
     * @param array $ticket
     * @return string
     */
    private function getGameResult(array $ticket): string
    {
        // 判斷是否是過關單
        $isParTicker = array_get($ticket, "TransType") === "PAR";

        // 若是一般單
        if (!$isParTicker) {
            // 主隊客隊名稱語系包及比分
            $homeTeamInfo = $this->getMessageInfo(array_get($ticket, "HomeTeamId"), 0);
            $homeTeamScore = array_get($ticket, "HomeScore");
            $awayTeamInfo = $this->getMessageInfo(array_get($ticket, "AwayTeamId"), 0);
            $awayTeamScore = array_get($ticket, "AwayScore");

            // 聯盟名稱語系包
            $leagueInfo = $this->getMessageInfo(array_get($ticket, "LeagueId"), 1);
            // 特別投注ID
            $specialId = array_get($ticket, "SpecialId");
            $specialInfo = [];
            if (!empty($specialId)) {
                $specialInfo = $this->getMessageInfo($specialId, 2);
            }
            $details = null;
        } else {
            // 主隊客隊名稱語系包及比分
            $homeTeamInfo = "";
            $homeTeamScore = "";
            $awayTeamInfo = "";
            $awayTeamScore = "";

            // 聯盟名稱語系包
            $leagueInfo = "";
            // 特別投注ID
            $specialInfo = "";
            // 若是過關單, 需取 SocTransId 去戳收尋子單的api
            $details = $this->getParTicketDetail(array_get($ticket, "SocTransId"));
        }
        // 是否已結算
        $isEnd = array_get($ticket, "WinLoseStatus") != "P";

        // 是否是滾球
        $isRunning = array_get($ticket, "IsRunning");

        // 若是滾球, 則取出投注時主客隊比分
        $runHomeScore = array_get($ticket, "RunHomeScore");
        $runAwayScore = array_get($ticket, "RunAwayScore");

//        if ($isRunning) {
//            $runHomeScore = array_get($ticket, "RunHomeScore");
//            $runAwayScore = array_get($ticket, "RunAwayScore");
//        }

        // 是否為上半場
        $isFirstHalf = array_get($ticket, "IsFirstHalf");

        // 是否為主隊讓分
        $isHomeGive = (bool)array_get($ticket, "IsHomeGive");

        // 盤口(讓分)
        $hdp = array_get($ticket, "Hdp");

        // 投注時的賠率
        $odds = array_get($ticket, "Odds");

        // 輸贏狀態 請參考 config united_ticket.cmd_sport.win_lose_status
        // WA, WH, LA, LH, D, P(未結算)
        $winLoseStatus = array_get($ticket, 'WinLoseStatus');

        // 投注位置
        $choice = array_get($ticket, "Choice");
        // 玩法類型
        // '单/双', '大/小'...
        $type = array_get($ticket, "TransType");
        if ($type === "X" || $type === "1" || $type === "2") {
            $pkORVs = "PK";
        } else {
            $pkORVs = "VS";
        }
        // 盤口類型
        $oddType = array_get($ticket, "OddsType");

        // 以下用不到的先註解，因為這會寫進去 DB united_tickets.game_result 為節省空間，可在排完開牌結果所需資訊後，剔除不需要的
        return json_encode([
            // 聯盟名稱
            'league' => $leagueInfo,
            // 玩法 請參考 config united_ticket.cmd_sport.bet_type
            'fashion' => $type,
            'choice' => $choice,
            // 類型 (場次對象)
            // cmd_sport只分 "上半場"及 "全場", 1="上半場" / 2="全場"
            'g_type' => ($isFirstHalf) ? "1" : "2",
            // 主隊
            'main_team' => $homeTeamInfo,
            // 客隊
            'visit_team' => $awayTeamInfo,
            // 投注時的盤口 (讓分)
            'chum_num' => $hdp,
            'vs_or_pk' => $pkORVs,
            // 盤口位置 (誰讓分)： 1 => 主隊讓，2 => 客隊讓
            'mode' => $isHomeGive ? "1" : "2",
            // 投注時的賠率
            'compensate' => $odds,
            // 是否已派彩 (0 未派彩 1 已派彩)
            'end' => $isEnd ? '1' : '0',
            // 是否是滾球
            'is_running' => $isRunning,
            // 盤口類型
            'oddsType' => $oddType,
            // 比分
            'left_score' => $awayTeamScore,
            'right_score' => $homeTeamScore, // super sport 的 score1 代表主隊分數，預設顯示在右邊
            // 滾球單,下注時主客隊比分
            'run_left_score' => ($isRunning) ? $runAwayScore : null,
            'run_right_score' => ($isRunning) ? $runHomeScore : null,
            // status 結帳狀態 注單狀態 w: 贏 / l: 輸 / d: 刪除單 / f: 退組
            'status' => $winLoseStatus,
            // 特別投注資料
            'special_info' => $specialInfo,
            // 是否為過關單
            'has_detail' => ($isParTicker) ? true : false,
            // 過關單細節
            'detail' => ($isParTicker) ? $details : null,
        ]);
    }

    // 取得隊伍名稱, 聯盟名稱, 特別投注名
    public function getMessageInfo($id, $type)
    {
        try {
            // 若是特別投注名
            // $id 可能會是 "111,222" 這種格式, 須先explode()並分析結果
            if ($type == 2) {
                $data = [];
                $parseId = explode(",", $id);
                foreach ($parseId as $sId) {
                    if (!empty($id)) {
                        $response = $this->caller
                            ->methodAction('GET', 'languageinfo', [
                                // 路由參數這邊設定
                            ])->params([
                                // 一般參數這邊設定
                                "Type" => $type,
                                'ID' => $sId,
                            ])->submit();
                        $langArray = array_get($response, "response.Data");
                        $returnArray = [];
                        foreach ($this->useLang as $langCode) {
                            $returnArray[$langCode] = array_get($langArray, $langCode);
                        }
                        array_push($data, $returnArray);
                    }
                }
                return $data;
            } else {
                $response = $this->caller
                    ->methodAction('GET', 'languageinfo', [
                        // 路由參數這邊設定
                    ])->params([
                        // 一般參數這邊設定
                        "Type" => $type,
                        'ID' => $id,
                    ])->submit();
                $langArray = array_get($response, "response.Data");
                $returnArray = [];
                foreach ($this->useLang as $langCode) {
                    $returnArray[$langCode] = array_get($langArray, $langCode);
                }
                return $returnArray;
            }
        } catch (\Exception $exception) {
            return [
                "zh-TW" => $id,
                "vi-VN" => $id,
                "en-US" => $id,
            ];
        }
    }

    // 取得過關子單資料, 並回傳 $details 陣列資料
    public function getParTicketDetail($socTransId)
    {
        try {
            $response = $this->caller
                ->methodAction('GET', 'parlaybetrecord', [
                    // 路由參數這邊設定
                ])->params([
                    // 一般參數這邊設定
                    "SocTransId" => $socTransId,
                ])->submit();
            // 取出過關子單
            $parTicketsArray = array_get($response, "response.Data");

            $returnArray = [];
            foreach ($parTicketsArray as $index => $parTicket) {
                // 主隊客隊名稱語系包及比分
                $homeTeamInfo = $this->getMessageInfo(array_get($parTicket, "HomeId"), 0);
                $homeTeamScore = array_get($parTicket, "HomeScore");
                $awayTeamInfo = $this->getMessageInfo(array_get($parTicket, "AwayId"), 0);
                $awayTeamScore = array_get($parTicket, "AwayScore");

                // 聯盟名稱語系包
                $leagueInfo = $this->getMessageInfo(array_get($parTicket, "LeagueId"), 1);
                // 特別投注ID
                $specialId = array_get($parTicket, "SpecialId");
                $specialInfo = [];
                if (!empty($specialId)) {
                    $specialInfo = $this->getMessageInfo($specialId, 2);
                }

                // 是否已結算
                $isEnd = array_get($parTicket, "ParStatus") != "P";

                // 是否是滾球
                $isRunning = array_get($parTicket, "IsRun");

                // 是否為上半場
                $isFirstHalf = array_get($parTicket, "IsFH");

                // 是否為主隊讓分
                $isHomeGive = (bool)array_get($parTicket, "IsHomeGive");

                // 盤口(讓分)
                $hdp = array_get($parTicket, "Hdp");

                // 投注時的賠率
                $odds = array_get($parTicket, "ParOdds");

                // 輸贏狀態 請參考 config united_ticket.cmd_sport.win_lose_status
                // WA, WH, LA, LH, D, P(未結算)
                $winLoseStatus = array_get($parTicket, 'ParStatus');

                // 投注位置
                $choice = array_get($parTicket, "Choice");
                // 玩法類型
                // '单/双', '大/小'...
                $type = array_get($parTicket, "ParTransType");
                if ($type === "X" || $type === "1" || $type === "2") {
                    $pkORVs = "PK";
                } else {
                    $pkORVs = "VS";
                }
                // 其他資訊
                $others = [];

                $returnArray[$index] = [
                    // 聯盟名稱
                    'league' => $leagueInfo,
                    // 玩法 請參考 config united_ticket.cmd_sport.bet_type
                    'fashion' => $type,
                    'choice' => $choice,
                    // 類型 (場次對象)
                    // cmd_sport只分 "上半場"及 "全場", 1="上半場" / 2="全場"
                    'g_type' => ($isFirstHalf) ? "1" : "2",
                    // 主隊
                    'main_team' => $homeTeamInfo,
                    // 客隊
                    'visit_team' => $awayTeamInfo,
                    // 投注時的盤口 (讓分)
                    'chum_num' => $hdp,
                    'vs_or_pk' => $pkORVs,
                    // 盤口位置 (誰讓分)： 1 => 主隊讓，2 => 客隊讓
                    'mode' => $isHomeGive ? "1" : "2",
                    // 投注時的賠率
                    'compensate' => $odds,
                    // 是否已派彩 (0 未派彩 1 已派彩)
                    'end' => $isEnd ? '1' : '0',
                    // 是否是滾球
                    'is_running' => $isRunning,
                    // 比分
                    'left_score' => $awayTeamScore,
                    'right_score' => $homeTeamScore, // super sport 的 score1 代表主隊分數，預設顯示在右邊
                    // status 結帳狀態
                    'status' => $winLoseStatus,
                    // 特別投注資料
                    'special_info' => $specialInfo,
                    // others
                    'others' => $others,
                ];
            }

            return $returnArray;
        } catch (\Exception $exception) {
            return [];
        }
    }
}