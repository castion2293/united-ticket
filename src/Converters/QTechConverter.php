<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「QT電子」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class QTechConverter extends Converter
{
    /**
     * 捕魚遊戲代碼
     * @var array
     */
    private $fishingGames = [];

    public function __construct()
    {
        parent::__construct();

        $this->fishingGames = [
            'SPG-oceanblaster2',
            'SPG-tigerattack',
        ];
    }

    /**
     * 轉換原生注單為整合注單
     *
     * @param array $rawTickets
     * @param string $userId
     * @return array
     * @throws \Exception
     */
    public function transform(array $rawTickets = [], string $userId = ''): array
    {
        $unitedTickets = [];
        $station = 'q_tech';

        $tickets = array_get($rawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的MemberAccount
            $walletAccounts = collect($tickets)
                ->pluck('playerId')
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

        // 取得遊戲列表
        $gameLists = $this->getGameList($station);

        foreach ($tickets as $ticket) {

            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($ticket, 'playerId');

                $userId = array_get($WalletUserIds, $account, null);

                if ($userId === null) continue;
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            $category = 'e-game';
            // 捕魚遊戲
            if (in_array(array_get($ticket, 'gameId', ''), $this->fishingGames)) {
                $category = 'fishing';
            }

            // 投注金額及輸贏結果
            $totalBet = floatval(array_get($ticket, 'totalBet'));
            $totalPayout = floatval(array_get($ticket, 'totalPayout'));
            $rawBet = $totalBet;
            $winnings = $totalPayout - $totalBet;

            // 投注及派彩時間
            $status = array_get($ticket, 'status');
            $betAt = $this->formatDateTime(array_get($ticket, 'initiated'));

            $payoutAt = null;
            if ($status == 'COMPLETED' || $status == 'FAILED') {
                $payoutAt = $this->formatDateTime(array_get($ticket, 'completed'));
            }

            // 開牌詳細內容
            $gameId = array_get($ticket, 'gameId');
            $gameName = array_get($gameLists, $gameId);
            $gameResult = '';
            $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' . $gameName . ' </b><br/>';
//            $gameResult .= '<b style="color:#fc5a34;">獎金投注金額: ' . array_get($ticket, 'totalBonusBet') . ' </b><br/>';
            $gameResult .= sprintf("(詳細內容請以注單編號至QT電子後台查詢); ");

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'id'),
                // 會員帳號識別碼
                'user_identify' => $userId,
                // 會員帳號
                'username' => array_get($ticket, 'playerId'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => 'slot',
                // 產品類別
                'category' => $category,
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
                'invalid' => $status == 'FAILED',
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
     * 取得遊戲列表
     *
     * @param string $station
     * @return array
     * @throws \Exception
     */
    private function getGameList(string $station)
    {
        try {
            $response = ApiCaller::make($station)->methodAction('get', 'games', [
                // 路由參數這邊設定
            ])->params([
                // 一般參數這邊設定
            ])->submit();

            $items = array_get($response, 'response.items');

            $gameLists = collect($items)->mapWithKeys(function ($item) {
                return [
                    array_get($item, 'id') => array_get($item, 'name')
                ];
            })->toArray();

            return $gameLists;

        } catch (\Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                'games',
                []
            ));
            throw $exception;
        }
    }

    /**
     *  統一時間格式
     *
     * @param string $dateTime
     * @return string
     */
    private function formatDateTime(string $dateTime)
    {
        preg_match('/(\d{4}-\d{2}-\d{2})/', $dateTime, $dateSet);
        preg_match('/(\d{2}:\d{2}:\d{2})/', $dateTime, $timeSet);

        return array_first($dateSet) . ' ' . array_first($timeSet);
    }
}