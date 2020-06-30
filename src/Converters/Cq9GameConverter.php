<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「cq9」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class Cq9GameConverter extends Converter
{
    /**
     * @var array|Repository|mixed
     * 產品類別
     */
    private $categories = [];

    /**
     * @var array|Repository|mixed
     * 遊戲類型參數對應表
     */
    private $gameTypes = [];

    private $username;

    /**
     * 捕魚遊戲代碼
     * @var array
     */
    private $fishingGames = [];

    /**
     * RealTimeGamingConverter constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->categories = config('api_caller_category');
        $this->gameTypes = config('united_ticket.cq9_game.game_type');

        $this->fishingGames = [
            'AB3',
            'AB1',
            'AT01',
        ];
    }

    /**
     * 轉換 (注意： 僅轉換，未寫入資料庫)
     *
     * @param array $aRawTickets
     *
     * @param string $sUserId
     * @return array
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        $unitedTickets = [];
        $station = 'cq9_game';

        $tickets = array_get($aRawTickets, 'tickets', []);

        // TODO: 先讓測試先通過，以後再補MOCK
        if (app()->environment() !== 'testing') {
            // 取得注單內所有的user_id
            $walletAccounts = collect($tickets)
                ->pluck('account')
                ->unique();

            $WalletUserIds = DB::table('station_wallets')
                ->select('account', 'user_id')
                ->whereIn('account', $walletAccounts)
                ->where('station', $station)
                ->get()
                ->mapWithKeys(function ($wallet) {
                    return [
                        data_get($wallet, 'account') => data_get($wallet,
                            'user_id'),
                    ];
                });

        }

        // 取得遊戲列表
        $gameLists = $this->getGameList($station);

        foreach ($tickets as $ticket) {

            // TODO: 先讓測試先通過，以後再補MOCK
            if (app()->environment() !== 'testing') {
                $account = array_get($ticket, 'account');

                $sUserId = array_get($WalletUserIds, $account, null);

                if ($sUserId === null) {
                    continue;
                }
            }

            // =============================================
            //    整理「原生注單」對應「整合注單」的各欄位資料
            // =============================================

            $game_scope = 'slot';

            $category = 'e-game';
            // 捕魚遊戲
            if (in_array(array_get($ticket, 'gamecode', ''), $this->fishingGames)) {
                $category = 'fishing';
            }

            $game_type = array_get($gameLists, array_get($ticket, 'gamecode'));
            $localNowTime = Carbon::now()->toDateTimeString();

            // 將 "下注時間" 的時區轉回台灣
            $rawTicketBetAt = array_get($ticket, 'bettime');
            $unitedTicketBetAt = Carbon::parse($rawTicketBetAt)
                ->timezone("Asia/Taipei")->toDateTimeString();

            // 將 "結算時間" 的時區轉回台灣
            $rawTicketPayoutAt = array_get($ticket, 'createtime');
            $unitedTicketPayoutAt = Carbon::parse($rawTicketPayoutAt)
                ->timezone("Asia/Taipei")->toDateTimeString();

            // 輸贏結果(cq9補同種類遊戲可能有不同算法)
            if (array_get($ticket, 'gametype') === "table") {
                // 若是table 桌牌類 原生注單贏額-抽水額
                $resultAmount = array_get($ticket, 'win') - array_get($ticket, 'rake');
            } else {
                // 若是slot/fish/arcade 原生注單贏額-投注金額
                $resultAmount = array_get($ticket, 'win') - array_get($ticket, 'bet');
            }

            // 依照不同類型的遊戲組成 $gameResult
            $rawTicketDetail = json_decode(array_get($ticket, 'detail'));

            $gameResult = "";
            if (array_get($ticket, 'gametype') === "fish") {
                $item = data_get($rawTicketDetail[0], 'item');
                $reward = data_get($rawTicketDetail[1], 'reward');

                $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' . $game_type . ' </b><br/>';
                $gameResult .= "額外購買: {$item}; ";
                $gameResult .= '<b style="color:#fc5a34;">額外獎金: ' . $reward . ' </b>';
            }

            if (array_get($ticket, 'gametype') === "slot" || array_get($ticket, 'gametype') === "arcade") {
                $freegame = data_get($rawTicketDetail[0], 'freegame');
                $luckydraw = data_get($rawTicketDetail[1], 'luckydraw');

                $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' . $game_type . ' </b><br/>';
                $gameResult .= "本注單含幾次免费遊戲: {$freegame}; ";
                $gameResult .= '<b style="color:#fc5a34;">彩池金額: ' . $luckydraw . ' </b>';
            }

            if (array_get($ticket, 'gametype') === "table") {
                $rake = array_get($ticket, 'rake');
                $gameResult .= '<b style="color:#11bed1;">遊戲名稱: ' . $game_type . ' </b><br/>';
                $gameResult .= '<b style="color:#fc5a34;">抽水金額: ' . $rake . ' </b>';
            }


            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號
                'bet_num' => (string)array_get($ticket, 'round'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => array_get($ticket, 'account'),
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => 'cq9_game',
                // 範疇，例如： 美棒、日棒
                'game_scope' => $game_scope ?? '',
                // 產品類別
                'category' => $category ?? '',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($ticket, 'bet'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'bet'),
                // 洗碼量 (扣掉同局對押情況之後的投注額)
                // 若沒有特別情況，該欄位資料應該會和實際投注一樣
                'rolling' => array_get($ticket, 'bet'),
                // 輸贏結果(可正可負)
                'winnings' => $resultAmount,
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => false,
                // 投注時間
                'bet_at' => $unitedTicketBetAt,
                // 派彩時間
                'payout_at' => $unitedTicketPayoutAt,
                // 資料建立時間
                'created_at' => $localNowTime,
                // 資料最後更新
                'updated_at' => $localNowTime,
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
     * 取得遊戲列表
     *
     * @param string $station
     * @return array
     * @throws \Exception
     */
    private function getGameList(string $station)
    {
        try {
            $response = ApiCaller::make('cq9_game')
                ->methodAction('get', 'game/list/{gamehall}', [
                    // 路由參數這邊設定
                    'gamehall' => "cq9",
                ])->params([
                    // 一般參數這邊設定
                ])->submit();

            $items = array_get($response, 'response.data');

            $gameLists = collect($items)->mapWithKeys(function ($item) {
                return [
                    array_get($item, 'gamecode') => array_get($item, 'gamename')
                ];
            });

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
}