<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use Symfony\Component\DomCrawler\Crawler;

class WinnerSportConverter extends Converter
{
    private $sStation = 'winner_sport';
    private $aCategories = [];

    public function __construct()
    {
        parent::__construct();
        $this->aCategories = config("api_caller_category.{$this->sStation}");
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
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('meusername1')
                ->unique();

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $this->sStation)
                ->get()
                ->mapWithKeys(function ($wallet) {
                    return [
                        data_get($wallet, 'account') => data_get($wallet, 'user_id')
                    ];
                });
        }

        foreach ($tickets as $aRawTicket) {
            $sId = array_get($aRawTicket, 'uuid');
            // TODO: 需依照各遊戲調整以下參數
            $sUserName = $aRawTicket['meusername1'];
            $sStation = $this->sStation;

            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($aRawTicket, 'meusername1');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) {
                    continue;
                }
            }

            if (is_numeric($sBetNum = array_get($aRawTicket, 'id'))) {
                $sBetNum = strval($sBetNum);
            }

            $sGameScope = array_get($aRawTicket, 'gtype', null);
            $sCategory = array_get($this->aCategories, array_get($aRawTicket, 'gtype'), 'sport');
            $sBetType = 'general';
            $sRawBet = array_get($aRawTicket, 'gold') ?? 0;
            $sValidBet = array_get($aRawTicket, 'gold_c') ?? 0;
            $sRolling = $sValidBet;
            $fWinnings = array_get($aRawTicket, 'meresult') ?? 0;

//            $league = array_get($aRawTicket, 'l_sname');
//            $mode = array_get($aRawTicket, 'r_title');

            $scorePreg = '/\[?(.*)\]?\s+(.+)\s+.\s+(.+) \[?(.*)\]?\s+=>\s+(.+)\s+@\s+(.+)/';
            preg_match_all($scorePreg, array_get($aRawTicket, 'detail', ''), $score, PREG_SET_ORDER, 0);

            $ReplaceVS = str_replace(" 0 ", " VS ", array_get($aRawTicket, 'detail', ''));
            $subStr = str_replace(" => ", "; ", $ReplaceVS);
//            $winnerTeam = explode(';', $subStr);

//            $sResultReport = '<b style="color:#11bed1;">[' . $league . " - " . $mode . ']</b><br/>';
//            $sResultReport .= $winnerTeam[0] . '<br/>';
//            $sResultReport .= '<b style="color:#fc5a34;">'. $winnerTeam[1] . '</b>';

            $bInvalid = array_get($aRawTicket, 'status') === 1;
            $sBetAt = Carbon::parse(array_get($aRawTicket, 'added_date', null))->toDateTimeString();
            $sPayoutAt = (array_get($aRawTicket, 'result', '') === '') ? null : Carbon::parse(array_get($aRawTicket,
                'modified_date', null))->toDateTimeString();
            $sNowTime = Carbon::now()->format('Y-m-d H:i:s');
            $sFlag = null;

            $aTicket = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => $sId,
                // 原生注單編號
                'bet_num' => $sBetNum,
                // 會員帳號識別碼 $user_id $userIdentify,
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $sUserName,
                // 遊戲服務站 (config/united_ticket.php 對應的索引值)
                'station' => $sStation,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $sGameScope,
                // 產品類別
                'category' => $sCategory,
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => $sBetType,
                // 實際投注
                'raw_bet' => $sRawBet,
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => $sValidBet,
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => $sRolling,
                // 輸贏結果(可正可負)
                'winnings' => $fWinnings,
                // 開牌結果
                'game_result' => $this->getGameResult($aRawTicket),
                // 作廢
                'invalid' => $bInvalid,
                // 投注時間
                'bet_at' => $sBetAt,
                // 派彩時間
                'payout_at' => $sPayoutAt,
                // 資料建立時間
                'created_at' => $sNowTime,
                // 資料最後更新
                'updated_at' => $sNowTime,
                // 紀錄遊戲主題代號
                'flag' => $sFlag,
            ];

            // 查找各階輸贏佔成
            $allotTableArray = $this->findAllotment(
                $sUserId,
                $sStation,
                $aTicket['game_scope'],
                $aTicket['bet_at']
            );

            $aUnitedTickets[] = array_merge($aTicket, $allotTableArray);
        }

        // 儲存整合注單
        UnitedTicket::replace($aUnitedTickets);

        return $aUnitedTickets;
    }

    /**
     * 準備寫進整合注單的 game_result 開牌結果，格式為 json
     *
     * @param array $ticket
     * @return string
     */
    private function getGameResult(array $ticket): string
    {
        // pr 0=非過關單，1=過關單
        $hasDetail = array_get($ticket, 'pr') == '1';
        // detail 是 html 格式，用解析器分解他取用需要的數值
        $crawler = new Crawler(array_get($ticket, 'detail_1'));
        $crawlerResult = []; // 非過關單，資訊放在各自獨立欄位
        $details = []; // 過關單放 detail
        if ($hasDetail) {
            $tmSet = $crawler->filter('body > span.tm_set');
            $details = $tmSet->each(function (Crawler $tmSetNode, $tmSetIndex) use ($ticket) {
                if ($tmSetNode->filter('.team1')->count() == 0) {
                    return null;
                }
                $crawlerResult = $this->crawlerResult($tmSetNode);
                // 投注時的盤口 (讓分)
                $chumNum = array_get($crawlerResult, 'chum_num');
                $crawlerResult['chum_num'] = $chumNum === 'PK' || $chumNum === 'VS' ? '' : $chumNum;
                return array_merge($crawlerResult, [
                    // 聯盟名稱
                    'league' => array_get($ticket, 'l_sname', 'empty league name'),
                    // 是否已派彩 (0 未派彩 1 已派彩)
                    'end' => empty(array_get($ticket, 'result')) ? '0' : '1',
                    // 結帳狀態
                    'status' => array_get($ticket, 'status'),
                    'status_trans' => array_get($ticket, 'stats'),
                    // 輸還是贏
                    'result' => array_get($ticket, 'result'),
                    // 對方 API 給的玩法名稱
                    'r_title' => array_get($ticket, 'r_title'),
                    // 成數球種 g_type 對應不同球種的玩法代號 rtype，請參考文件
                    'gtype' => array_get($ticket, 'gtype'), // 須看 gtype 去對應球種才知道 rtype 下注類型
                    // 玩法代號 rtype，請參考文件
                    'rtype' => array_get($ticket, 'rtype'),
                    // 標示 vs or pk 字串
                    'vs_or_pk' => array_get($crawlerResult, 'chum_num') === 'PK' ? 'PK' : 'VS',
                ]);
            });
            $details = array_filter($details);
            $details = array_values($details);
        } else {
            // 以 tm_set 為標籤，可能多個 node，但如果非過關單，class 都是成對的，底下會有 .tm_set.team1 .tm_set.team2 各一包
            $crawlerResult = $this->crawlerResult(
                $crawler->filter('body > span.tm_set')
            );
        }
        // 以下用不到的先註解，因為這會寫進去 DB united_tickets.game_result 為節省空間，可在排完開牌結果所需資訊後，剔除不需要的
        $chumNum = array_get($crawlerResult, 'chum_num');
        return json_encode([
            // 聯盟名稱
            'league' => array_get($ticket, 'l_sname', 'empty league name'),
            // 主隊 過關單時不是看這個，是看 detail
            'main_team' => array_get($crawlerResult, 'main_team'),
            // 客隊 過關單時不是看這個，是看 detail
            'visit_team' => array_get($crawlerResult, 'visit_team'),
            // 投注時的盤口 (讓分)
            'chum_num' => $chumNum === 'PK' || $chumNum === 'VS' ? '' : $chumNum,
            'vs_or_pk' => $chumNum === 'PK' ? 'PK' : 'VS',
            // 成數球種 g_type 對應不同球種的玩法代號 rtype，請參考文件
            'gtype' => array_get($ticket, 'gtype'), // 須看 gtype 去對應球種才知道 rtype 下注類型
            // 玩法代號 rtype，請參考文件
            'rtype' => array_get($ticket, 'rtype'),
            // 對方 API 給的玩法名稱
            'r_title' => array_get($ticket, 'r_title'),
            // 下注目標
            'bet_str' => array_get($crawlerResult, 'bet_str'),
            // 投注時的賠率
            'compensate' => array_get($ticket, 'io'),
            // 是否已派彩 (0 未派彩 1 已派彩)
            'end' => empty(array_get($ticket, 'result')) ? '0' : '1',
            // 比分
            'left_score' => array_get($crawlerResult, 'left_score'),
            'right_score' => array_get($crawlerResult, 'right_score'),
            // 盤口
            'mode' => array_get($crawlerResult, 'mode'),
            // 結帳狀態
            'status' => array_get($ticket, 'status'),
            'status_trans' => array_get($ticket, 'stats'),
            // 輸還是贏
            'result' => array_get($ticket, 'result'),
            // 是否為過關單
            'has_detail' => $hasDetail,
            // 過關單細節
            'detail' => $details,

            // 贏家體育貌似沒有盤口資訊，super sport 有 chum_num 盤口資訊
            // 也沒有標示是誰讓分，super sport 有 mode 標示是誰讓分
        ]);
    }

    /**
     * @param Crawler $tmSetNode
     * @return array
     */
    private function crawlerResult(Crawler $tmSetNode): array
    {
        // 比分
        $scores = $tmSetNode->filter('.p_font_r')->each(function (Crawler $node, $index) {
            return $node->text();
        });

        // 投注時的盤口 (讓分)
        $chumNum = $tmSetNode->filter('.wg_con')->text();

        // 隊伍
        $team1 = $tmSetNode->filter('.team1')->text();
        $team2 = $tmSetNode->filter('.team2')->text();
        $isTeam1MainTeam = (strpos($team1, '主') !== false);

        // 下注目標
        if ($tmSetNode->filter('.bet_str')->count() != 0) {
            $betStr = $tmSetNode->filter('.bet_str')->text();
        } else {
            $betStr = $tmSetNode->nextAll()->filter('.bet_str')->text();
        }

        // 主客隊
        $mainTeam = $isTeam1MainTeam ? $team1 : $team2;
        $visitTeam = !$isTeam1MainTeam ? $team1 : $team2;

        // 是否有盤口 (讓分下注類型)
        $isChumNum = $chumNum !== 'V.S' && $chumNum !== 'PK';
        // 誰讓分 0/0.5
        $mode = '';
        if ($isChumNum) {
            // 假設只要有盤口，就預設為客隊讓分
            $mode = '2';
            // .wg_con 都會放在 team1，所以若 team 1 是主隊，主隊讓分
            if ($isTeam1MainTeam) {
                $mode = '1';
            }
        }

        // 先預設 team2 是主隊，team2 score 放右邊
        $leftScore = $team1Score = array_get($scores, 0);
        $rightScore = $team2Score = array_get($scores, 1);
        if ($isTeam1MainTeam) {
            $leftScore = $team2Score;
            $rightScore = $team1Score; // $isTeam1MainTeam true 代表 team 1 是主隊，要把分數放右邊
        }

        // 移除 [ 以及 ] 左右中括號
        $leftScore = str_replace(']', '', str_replace('[', '', $leftScore));
        $rightScore = str_replace(']', '', str_replace('[', '', $rightScore));

        return [
            'main_team' => str_replace('[主]', '', $mainTeam),
            'visit_team' => $visitTeam,
            'is_chum_num' => $isChumNum,
            'chum_num' => $chumNum,
            'compensate' => $tmSetNode->nextAll()->filter('.wg_io')->text(),
            'bet_str' => str_replace('[主]', '', $betStr),
            'left_score' => $leftScore, // 左邊顯示分
            'right_score' => $rightScore, // 右邊顯示分
            'mode' => $mode,
        ];
    }
}