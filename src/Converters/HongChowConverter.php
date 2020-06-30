<?php
//
//namespace SuperPlatform\UnitedTicket\Converters;
//
//use Illuminate\Support\Facades\DB;
//use SuperPlatform\StationWallet\StationWallet;
//use SuperPlatform\NodeTree\NodeTree;
//use SuperPlatform\AllotTable\AllotTable;
//use App\Models\Agent;
//use SuperPlatform\UnitedTicket\Models\UnitedTicket;
//
///**
// * Class HongChowConverter
// * @package SuperPlatform\UnitedTicket\Converters
// */
//class HongChowConverter extends Converter
//{
//    /**
//     * @var StationWallet
//     */
//    private $stationWallet;
//    /**
//     * @var NodeTree
//     */
//    private $nodeTree;
//    /**
//     * @var AllotTable
//     */
//    private $allotTable;
//
//    private $station = 'hong_chow';
//    private $gameTypes;
//    private $categories;
//    private $username;
//
//    /**
//     * HongChowConverter constructor.
//     * @param StationWallet $stationWallet
//     * @param NodeTree $nodeTree
//     * @param AllotTable $allotTable
//     * @param Agent $agent
//     */
//    public function __construct(StationWallet $stationWallet, NodeTree $nodeTree, AllotTable $allotTable, Agent $agent)
//    {
//        $this->stationWallet = $stationWallet;
//        $this->nodeTree = $nodeTree;
//        $this->allotTable = $allotTable;
//        $this->agent = $agent;
//
//        $this->gameTypes = config('united_ticket.hong_chow.game_type');
//        $this->categories = config('api_caller_category');
//    }
//
//    /**
//     * 轉換原生注單為整合注單
//     *
//     * @param array $rawTickets
//     * @param string $user_id
//     * @return array
//     */
//    public function transform(array $rawTickets = [], $user_id = '')
//    {
//        $unitedTickets = [];
//
//        foreach (array_get($rawTickets, 'tickets', []) as $ticket) {
//            $this->username = explode('@', $ticket['user_name'])[0];
//
//            // TODO: 先讓測試先通過，以後再補MOCK
//            if (app()->environment() !== 'testing') {
//                $oUserLoginRecord = DB::table('station_login_records')->where('account', $this->username)->first();
//
//                if ($oUserLoginRecord === null) continue;
//
//                $user_id = $oUserLoginRecord->user_id;
//            }
//
//            // 整理「原生注單」對應「整合注單」的各欄位資料
//            $betAt = array_get($ticket, 'bettime', null);
//
//            if (is_numeric($betNum = array_get($ticket, 'bet_id'))) $betNum = strval($betNum);
//
//            $payoutAt = array_get($ticket, 'reckondate', null);
//            $username = $this->username;
//            $now = date('Y-m-d H:i:s');
//            $game_scope = array_get($this->gameTypes, array_get($ticket, 'game_id'));
//            $category = array_get($this->categories, "{$this->station}.{$game_scope}", '');
//
//            $gameResult = '';
//            switch (array_get($ticket, 'bettype')) {
//                case 0:
//                    $gameResult .= '游戏类型: 单注' . PHP_EOL;
//                    $gameResult .= "游戏名: {$ticket['game_name']}" . PHP_EOL;
//                    $gameResult .= "赛事名: {$ticket['match_name']}" . PHP_EOL;
//                    $gameResult .= "比赛名: {$ticket['race_name']}" . PHP_EOL;
//                    $gameResult .= "盘口名: {$ticket['han_name']}" . PHP_EOL;
//                    $gameResult .= "队伍 1: {$ticket['team1_name']}" . PHP_EOL;
//                    $gameResult .= "队伍 2: {$ticket['team2_name']}" . PHP_EOL;
//                    $gameResult .= "局数: {$ticket['round']}" . PHP_EOL;
//                    $gameResult .= "投注选项: {$ticket['part_id']}" . PHP_EOL;
//                    $r = '';
//                    $r = ($ticket['part_id'] === $ticket['result']) ? '中奖' : '未中奖';
//                    if ($ticket['result'] === null) $r = '未开奖';
//                    if ($ticket['result'] === 0) $r = '无赛果或注单退回';
//                    $gameResult .= "开奖结果: {$ticket['result']}($r)" . PHP_EOL;
//                    break;
//                case 1:
//                    $gameResult .= '游戏类型: 串注' . PHP_EOL;
//                    $r = '';
//                    if ($ticket['result'] === null) $r = '未开奖';
//                    $r = ($ticket['part_id'] === $ticket['result']) ? '中奖' : '未中奖';
//                    if ($ticket['result'] === 0) $r = '无赛果或注单退回';
//                    $gameResult .= "开奖结果: {$ticket['result']}($r)" . PHP_EOL;
//                    break;
//            }
//
//            // 從每張注單去取出各個代理商的占成值
//            $allotmentsTable = $this->findAllotTable($user_id, $this->station, $game_scope, $betAt);
//
//            $winnings = array_get($ticket, 'returnamount') === null ? 0 : array_get($ticket, 'returnamount') - array_get($ticket, 'betamount');
//            if (strtotime(array_get($ticket, 'reckondate')) <= strtotime($now) &&
//                array_get($ticket, 'bettype') === 1) {
//                $winnings = array_get($ticket, 'returnamount') === null ? 0 - array_get($ticket, 'betamount') : array_get($ticket, 'returnamount') - array_get($ticket, 'betamount');
//            }
//            if ($ticket['result'] === 0) $winnings = array_get($ticket, 'refundamount');
//
//            $rawTicketArray = [
//                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
//                'id' => array_get($ticket, 'uuid'),
//                // 原生注單編號
//                'bet_num' => $betNum,
//                // 會員帳號識別碼$user_id$userIdentify,
//                'user_identify' => $user_id,
//                // 會員帳號
//                'username' => $username,
//                // 遊戲服務站(config/united_ticket.php 對應的索引值)
//                'station' => $this->station,
//                // 範疇，例如： 美棒、日棒
//                'game_scope' => $game_scope ?? '',
//                // 產品類別
//                'category' => $category ?? '',
//                // 押注類型，例如： 模式+場次+玩法
//                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
//                'bet_type' => 'general',
//                // 實際投注
//                'raw_bet' => array_get($ticket, 'betamount'),
//                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
//                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
//                'valid_bet' => array_get($ticket, 'betamount'),
//                // 洗碼量 (扣掉同局對押情況之後的投注額)
//                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
//                'rolling' => array_get($ticket, 'betamount'),
//                // 輸贏結果(可正可負)
//                'winnings' => $winnings,
//                // 開牌結果
//                'game_result' => $gameResult,
//                // 作廢
//                'invalid' => array_get($ticket, 'State') === 3,
//                // 投注時間
//                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
//                // 派彩時間
//                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
//                // 資料建立時間
//                'created_at' => $now,
//                // 資料最後更新
//                'updated_at' => $now
//            ];
//
//            // 先把占程表做成1個Array，然後再跟rawTickeyArray merge
//            $i = 0;
//            $allotTableArray = [];
//
//            foreach ($allotmentsTable as $table) {
//                $allotTableArray["depth_{$i}_identify"] = $table['username'];
//                $allotTableArray["depth_{$i}_ratio"] = $table['ratio'];
//                $i++;
//            }
//
//            $unitedTickets[] = array_merge($rawTicketArray, $allotTableArray);
//        }
//
//        // 儲存整合注單
//        UnitedTicket::replace($unitedTickets);
//
//        // 記錄取得最新注單的 sync version 標記
//        session(['hong_chow_sync_version' => array_get($rawTickets, 'sync_version')]);
//
//        return $unitedTickets;
//    }
//
//    /**
//     * @param $userIdentify
//     * @param $station
//     * @param $game_scope
//     * @param $betAt
//     * @return array
//     */
//    private function findAllotTable($userIdentify, $station, $game_scope, $betAt): array
//    {
//        $userTreeNode = $this->nodeTree->findNodeLatestVersion($userIdentify);
//
//        $allotments = [];
//
//        if (is_string($userTreeNode['ancestor_ids'])) {
//            $userTreeNode['ancestor_ids'] = explode(',', $userTreeNode['ancestor_ids']);
//        }
//
//        foreach ($userTreeNode['ancestor_ids'] as $index => $ancestor_id) {
//            $allotTable = $this->allotTable->getAllotmentByIdStationScope(
//                $ancestor_id, // 代理識別碼
//                $station, // 遊戲站
//                $game_scope, // 類型
//                $betAt // 查詢的時間點
//            );
//            $allotments[$index] = [
//                'level' => $index, // 層級深度
//                'username' => $this->agent->find($ancestor_id)->username, // 代理帳號
//                'ratio' => array_get($allotTable, 'allotment', 0), // 代理佔成
//            ];
//        }
//
//        return $allotments;
//    }
//}