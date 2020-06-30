<?php

namespace SuperPlatform\UnitedTicket\Converters;

use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「SUPER 體彩」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class SuperConverter extends Converter
{
    /**
     * @var array 玩法：球類
     */
    private $gameTypes;

    /**
     * @var array
     */
    private $fashion;

    /**
     * @var array 玩法：場次類型：全場、上半、下半...
     */
    private $g_type;

    /**
     * @var array
     */
    private $mode;

    /**
     * @var array 結帳狀態
     */
    private $status;

    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.super_sport.game_type');
        $this->fashion = config('united_ticket.super_sport.fashion');
        $this->g_type = config('united_ticket.super_sport.g_type');
        $this->mode = config('united_ticket.super_sport.mode');
        $this->status = config('united_ticket.super_sport.status');
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
        $unitedTickets = [];

        foreach (array_get($aRawTickets, 'tickets', []) as $ticket) {
            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($ticket, 'm_date', null);

            if (is_numeric($betNum = array_get($ticket, 'sn'))) {
                $betNum = strval($betNum);
            }

            $countDate = array_get($ticket, 'count_date');
            $payoutAt = (array_get($ticket, 'end') === '1') || (array_get($ticket,
                    'status_note') === 'D') ? array_get($ticket, 'payout_time', $countDate) : null;

            // 如果是刪除單 派彩時間會是0000-00-00 00:00:00，所以要改成派彩日期
            if (array_get($ticket, 'status_note') === 'D') {
                $payoutAt = $countDate;
            }

            if (array_get($ticket, 'end') === '1' && $payoutAt == '0000-00-00 00:00:00') {
                $payoutAt = $countDate;
            }

            $username = array_get($ticket, 'm_id');
            $now = date('Y-m-d H:i:s');
            $station = 'super_sport';
            $game_scope = array_get($this->gameTypes, array_get($ticket, 'team_no'));

            // 整理派彩結果
//            $league = array_get($ticket, 'league');
            $mainTeam = array_get($ticket, 'main_team');
            $visitTeam = array_get($ticket, 'visit_team');
//            $mvSet = array_get($fashion, "mv_set.{$ticket['mv_set']}");
//            $mode = (filled($ticket['mode'])) ? array_get($this->mode, $ticket['mode']) : null;
//            $chumNum = array_get($ticket, 'chum_num');
//            $compensate = array_get($ticket, 'compensate');
//            $status = array_get($this->status, $ticket['status']);
            $score1 = array_get($ticket, 'score1');
            $score2 = array_get($ticket, 'score2');
//            $detail = preg_replace('/&nbsp;/', '', strip_tags(array_get($ticket, 'matter')));

            if ($score1 > $score2) {
                $winTeam = $mainTeam;
            } else {
                $winTeam = $visitTeam;
            }

            $rawBet = array_get($ticket, 'gold');
            $validBet = array_get($ticket, 'bet_gold');
            $winnings = array_get($ticket, 'result_gold');


//            $gameResult = '<b style="color:#11bed1;">[' . $league . " - " . $mode . ']</b><br/>';
//            $gameResult .= $visitTeam . " VS " . $mainTeam . "(主) " . $chumNum . "; ";
//            $gameResult .= '<b style="color:#fc5a34;">' . $winTeam . " {$compensate}" . ' [' . $score1 . ":" . $score2 . ']</b>';
//
//            if (array_get($ticket, 'detail') !== 'null') {
//                $gameResult .= '(過關單)';
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
                'game_scope' => $game_scope ?? '',
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
                // 開牌結果
                'game_result' => $this->getGameResult($ticket),
                // 作廢
                'invalid' => array_get($ticket, 'status_note') === 'D',
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

        return $unitedTickets;
    }

    /**
     * 準備寫進整合注單的 game_result 開牌結果，格式為 json
     *
     * @param array $ticket
     * @return string
     */
    private function getGameResult(array $ticket): string
    {
        // 主隊客隊
        $mainTeam = array_get($ticket, 'main_team');
        $visitTeam = array_get($ticket, 'visit_team');

        // 比分
//        $score1 = array_get($ticket, 'score1');
//        $score2 = array_get($ticket, 'score2');

        // 是否已結算
        $isEnd = $this->isEnd($ticket);

        // 結帳狀態 請參考 config united_ticket.super_sport.status
        $ticketStatus = array_get($ticket, 'status');
        $ticketStatusTrans = $ticketStatus ? array_get($this->status, $ticketStatus) : '';

        // 球種
        $teamNo = array_get($ticket, 'team_no');
        if (array_get($ticket, 'detail') != 'null') {
            $details = str_replace('score1', 'right_score', array_get($ticket, 'detail'));
            $details = str_replace('score2', 'left_score', $details);
        } else {
            $details = null;
        }
        // 以下用不到的先註解，因為這會寫進去 DB united_tickets.game_result 為節省空間，可在排完開牌結果所需資訊後，剔除不需要的
        $chumNum = array_get($ticket, 'chum_num');
        return json_encode([
            // 聯盟名稱
            'league' => array_get($ticket, 'league', 'empty league name'),
            // 玩法 請參考 config united_ticket.super_sport.fashion
            'fashion' => array_get($ticket, 'fashion'),
            'mv_set' => array_get($ticket, 'mv_set'),
            // 球類
            'team_no' => $teamNo,
            // 類型 (場次對象) 請參考 config united_ticket.super_sport.g_type
            'g_type' => array_get($ticket, 'g_type'),
            // 主隊
            'main_team' => $mainTeam,
            // 客隊
            'visit_team' => $visitTeam,
            // 獲勝隊伍
//            'winner' => $this->getWinner($isEnd, $mainTeam, $visitTeam, $score1, $score2),
            // 投注時的盤口 (讓分)
            'chum_num' => $chumNum === 'VS' ? '' : $chumNum,
            'vs_or_pk' => array_get($ticket, 'fashion') === '3' ? 'PK' : 'VS',
            // 足球基準分
            'playing_score' => array_get($ticket, 'playing_score', ''),
            // 盤口位置 (誰讓分)： 1 => 主隊讓，2 => 客隊讓
            'mode' => array_get($ticket, 'mode'),
            // 投注時的賠率
            'compensate' => array_get($ticket, 'compensate'),
            // 是否已派彩 (0 未派彩 1 已派彩)
            'end' => $isEnd ? '1' : '0',
            // 派彩修正紀錄
            'updated_msg' => array_get($ticket, 'updated_msg'),
            // 比分
            'left_score' => array_get($ticket, 'score2'),
            'right_score' => array_get($ticket, 'score1'), // super sport 的 score1 代表主隊分數，預設顯示在右邊
            // status 結帳狀態 注單狀態 w: 贏 / l: 輸 / d: 刪除單 / f: 退組
            'status' => $ticketStatus,
            'status_trans' => $ticketStatusTrans, // 若主專案有實作多語系，應從主專案進行翻譯，這邊先將讀取 config 翻譯結果存到 DB
            // 是否為過關單
            'has_detail' => !empty(array_get($ticket, 'detail')) && array_get($ticket, 'detail') != 'null',
            // 過關單細節
            'detail' => $details,
        ]);
    }

    /**
     * 取得獲勝隊伍
     *
     * 若需要後端判斷誰是獲勝隊伍時，會調用到此方法，但目前不會用
     *
     * @param $isEnd
     * @param $mainTeam
     * @param $visitTeam
     * @param $score1
     * @param $score2
     * @return string
     */
    private function getWinner($isEnd, $mainTeam, $visitTeam, $score1, $score2): string
    {
        if (!$isEnd) {
            return '';
        }

        if ($score1 > $score2) {
            return $mainTeam;
        }
        return $visitTeam;
    }

    /**
     * 是否已派彩（已結算）
     * @param array $ticket
     * @return bool
     */
    private function isEnd(array $ticket): bool
    {
        return array_get($ticket, 'end', 0) == '1' ||
            array_get($ticket, 'end', 0) == 1;
    }
}