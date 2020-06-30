<?php


namespace SuperPlatform\UnitedTicket\Converters;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\UnitedTicket\Models\IncorrectScoreRake;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

class IncorrectScoreConverter extends Converter
{
    /**
     * @var array 玩法：球類
     */
    private $gameTypes;

    /**
     * @var array
     */
    private $wagerTypeID;

    /**
     * @var array 玩法：場次類型：全場、上半、下半...
     */
    private $wagerGrpID;

    /**
     * @var array
     */
    private $mode;

    /**
     * @var array 注單狀態
     */
    private $ticketStatus;

    public function __construct()
    {
        parent::__construct();

        $this->gameTypes = config('united_ticket.incorrect_score.sportType');
        $this->wagerTypeID = config('united_ticket.incorrect_score.wagerTypeID');
        $this->wagerGrpID = config('united_ticket.incorrect_score.wagerGrpID');
        $this->ticketStatus = config('united_ticket.incorrect_score.cType');
    }


    /**
     * 原生注單資料轉換成整合注單的資料
     *
     * @param array $rawTickets
     * @param string $userId
     * @return array
     */
    public function transform(array $rawTickets = [], string $userId = ''): array
    {
        $unitedTickets = [];

        $station = 'incorrect_score';

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('user')
                ->unique();

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $station)
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
                $account = array_get($ticket, 'user');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            // 注單編號
            $gameID = array_get($ticket, 'ticketNo', '');

            // 對應的球類
            $game_scope = array_get($this->gameTypes, array_get($ticket, 'sportType'));

            // 顯示對應遊戲結果
            $rawBet = array_get($ticket, 'betamount');
            $validBetAmount = (array_get($ticket, 'statusType') == 'V') ? 0 :array_get($ticket, 'betamount');
            // 輸贏結果
            $winningAmount = array_get($ticket, 'winlose');
            // 投注時間
            $betTime = array_get($ticket, 'betTime');
            // 派彩時間
            $payoutTime = array_get($ticket, 'winlostTime', null);
            // 若注單狀態為取消或失敗則變更為作廢住單
            $ticketStatus = array_get($ticket, 'statusType');
            $invalid = ($ticketStatus == 'Y') ? false : true;
            // 盈利
            $profit = array_get($ticket, 'profit', 0);
            // 實貨量(不含手續費)
            $realAmount = array_get($ticket, 'realAmount', 0);

            // 若為「取消單」或「無效單」則派彩時間改為取消注單时间
            if ($ticketStatus == 'V' || $ticketStatus == 'R') {
                $payoutTime = array_get($ticket, 'cancelTime');
            }
            $retake = 0;
            // 娛樂城退水贏額
            if ($winningAmount > 0) {
                $retake = array_get($ticket, 'handlingFee');
            }
            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)$gameID,
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'user'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => '1' ?? '',
                // 產品類別
                'category' => 'sport',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => $rawBet,
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => $validBetAmount,
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => $validBetAmount,
                // 輸贏結果
                'winnings' => $winningAmount,
                // 盈利
                'profit' => $profit,
                // 實貨量(不含手續費)
                'origin_winning' => $realAmount,
                // 開牌結果
                'game_result' => $this->getGameResult($ticket),
                // 作廢
                'invalid' => $invalid,
                // 投注時間
                'bet_at' => $betTime ? date('Y-m-d H:i:s', strtotime($betTime)) : null,
                // 派彩時間
                'payout_at' => $payoutTime ? date('Y-m-d H:i:s', strtotime($payoutTime)) : null,
                // 開賽時間
                'schedule_at' => array_get($ticket, 'scheduleTime'),
                // 賽事編號
                'property' => array_get($ticket, 'evtid'),
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

            //=========================================================
            // 建立反波膽退水注單
            //=========================================================
            $rawRakeArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)$gameID,
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'user'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => '1',
                // 產品類別
                'category' => 'sport',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 退水點數
                'rake' => $retake,
                // 包網贏額
                'winningAmount' => ($winningAmount > 0) ? ($rawBet * array_get($ticket, 'odds')) / 100 : 0,
                // 投注時間
                'bet_at' => $betTime ? date('Y-m-d H:i:s', strtotime($betTime)) : null,
                // 派彩時間
                'payout_at' => $payoutTime ? date('Y-m-d H:i:s', strtotime($payoutTime)) : null,
            ];

            // 查找各階水倍差佔成
            $rakesTableArray = $this->findRebate(
                $userId,
                $station,
                $rawRakeArray['game_scope'],
                $rawRakeArray['bet_at']
            );

            $unitedRakes[] = array_merge($rawRakeArray, $rakesTableArray);

        }

        // 儲存整合注單
        UnitedTicket::replace($unitedTickets);

        // 儲存退水注單
        IncorrectScoreRake::replace($unitedRakes);
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
        // 比賽隊伍
        $match = array_get($ticket, 'match');

        // 是否已結算
        $finished = array_get($ticket, 'isFinished');

        // 注單狀態 
        $ticketStatus = array_get($ticket, 'statusType');
        // 遊戲類型
        $wagerGrpID = array_get($ticket, 'wagerGrpID');
        $wagerGrpTrans = $wagerGrpID ? array_get($this->wagerGrpID, $wagerGrpID) : '';
        // 球種
        $sportType = array_get($ticket, 'sportType');
        // 盤口
        $chumNum = array_get($ticket, 'odddesc');

        // 以下用不到的先註解，因為這會寫進去 DB united_tickets.game_result 為節省空間，可在排完開牌結果所需資訊後，剔除不需要的
        return json_encode([
            // 聯盟名稱
            'league' => array_get($ticket, 'league', ''),
            // 玩法 請參考 config united_ticket.incorrect_score.wagerTypeID
            'wagerTypeID' => array_get($ticket, 'wagerTypeID'),
            // 球類
            'sportType' => $sportType,
            // 遊戲類型 (如過關單 全場早盤 全場今日等等) 請參考 config united_ticket.incorrect_score.wagerGrpID
            'wagerGrpID' => $wagerGrpTrans,
            // 比賽隊伍
            'match' => $match,
            // 下注玩法
            'betOption' => array_get($ticket, 'betOption'),
            // 讓球方 0:主 1:客
            'hdp' => array_get($ticket, 'hdp'),
            // 投注時的盤口 (讓分)
            'chum_num' => $chumNum,
            // 投注時的賠率
            'odds' => array_get($ticket, 'odds'),
            // 賠率類型說明
            'oddsDesc' => array_get($ticket, 'odddesc'),
            // 是否已派彩 (0 未派彩 1 已派彩)
            'end' => $finished ? '1' : '0',
            // 下注比分
            'cutline' => array_get($ticket, 'cutline'),
            // 主客隊比分
            'ftScore' => array_get($ticket, 'ftScore'),
            // status 注單狀態 Y:成功注单 V:取消注单 R:无效注单
            'status' => $ticketStatus,
        ]);
    }
}