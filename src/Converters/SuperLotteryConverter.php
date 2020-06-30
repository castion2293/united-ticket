<?php

namespace SuperPlatform\UnitedTicket\Converters;

use Ariby\Ulid\Ulid;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use SuperPlatform\UnitedTicket\Models\SuperLotteryRake;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;

/**
 * 「Lottery 101 彩球」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class SuperLotteryConverter extends Converter
{
    /**
     * @var array 遊戲類型
     */
    private $gameScope = [];

    /**
     * @var array 玩法類型編號
     */
    private $gameType = [];

    /**
     * @var array 玩法編號
     */
    private $betType = [];

    /**
     * 檢查彩球注單已經結算
     *
     * @var bool
     */
    private $isPayout = false;

    /**
     * 六合(11) 結算時間(23:00:00)
     * 大樂(12) 結算時間(22:00:00)
     * 539(13) 結算時間(22:00:00)
     * 天天樂(22) 結算時間(12:00:00)
     *
     * @var array
     */
    private $payoutTime = [];

    /**
     * @var array|Repository|mixed
     * 產品類別
     */
    private $categories = [];

    public function __construct()
    {
        parent::__construct();

        $this->gameScope = config('united_ticket.super_lottery.game_scopes');
        $this->gameType = config('united_ticket.super_lottery.game_type');
        $this->betType = config('united_ticket.super_lottery.bet_type');

        // 初始化結算時間
        $this->payoutTime = [
            '11' => '21:30:00', // 星期一、六 /今彩539 3星 4星
            '12' => '20:50:00', // 星期一、四 / 威力彩
            '13' => '20:30:00', // 星期二、五 / 大樂透
            '22' => '10:25:00', // 星期二、四 六或日 / 香港六合彩
        ];
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

            // 下注帳號
            $account = explode('@', array_get($ticket, 'account'))[1];

//            if (app()->environment() !== 'testing') {
//                $oUserLoginRecord = DB::table('station_login_records')->where('account', $account)->first();
//
//                if ($oUserLoginRecord === null) continue;
//
//                $user_id = $oUserLoginRecord->user_id;
//            }

            // 遊戲館
            $station = 'super_lottery';

            // 遊戲類型
            $gameScope = array_get($this->gameScope, array_get($ticket, 'game_id'));

            // 開獎狀態 0:未開講 1:已開獎
            $state = array_get($ticket, 'state');

            // 注單狀態(0:有效單 1:已刪除
            $status = array_get($ticket, 'status');

            // 下注金額 (有效單才計算，刪除單就歸0)
            $cmount = ($status == 0) ? array_get($ticket, 'cmount') : '0';

            // 中獎金額 (有效單才計算，刪除單就歸0)
            $gold = ($status == 0) ? array_get($ticket, 'gold') : 0;

            // 退水金額 (有效單才計算，刪除單就歸0)
            $retake = ($status == 0) ? array_get($ticket, 'retake') : 0;

            // 輸贏結果(可正可負) 中獎金額 + 會員退水 - 下注金額 / (有效單才計算，刪除單就歸0)
            $winnings = ($status == 0) ? $gold + $retake - $cmount : 0;

            // 投注時間
            $batAt = array_get($ticket, 'bet_time');

            // 派彩時間 (開獎狀態: 1 才會有派彩時間)
            $payoutAt = ($state == 1) ? array_get($ticket, 'count_date') . ' ' . array_get($this->payoutTime, array_get($ticket, 'game_id')) : null;

            // 刪除單會直接轉成派彩狀態
            if ($status == 1) {
                $payoutAt = $batAt;
            }

            // 詳細
            $gameResult = '';
            $gameResult .= '<b style="color:#11bed1;">' . array_get($this->betType, array_get($ticket, 'bet_type'), '') . ' - ' . array_get($ticket, 'detail', '') . ' ' . array_get($ticket, 'odds', '') . ' </b><br/>';
            $gameResult .= '<b style="color:#fc5a34;">[' . array_get($ticket, 'lottery', '') . '] </b>';


//            $gameResult = '';
//            $gameResult .= '<div>' . '期數名稱: ' . array_get($ticket, 'name', '') . '</div>';
//            $gameResult .= '<div>' . '/ 玩法類型: ' . array_get($this->gameType, array_get($ticket, 'game_type'), '') . '</div>';
//            $gameResult .= '<div>' . '/ 玩法: ' . array_get($this->betType, array_get($ticket, 'bet_type'), '') . '</div>';
//            $gameResult .= '<div>' . '/ 下注內容: ' . array_get($ticket, 'detail', '') . '</div>';
//            $gameResult .= '<div>' .'/ 賠率: ' . array_get($ticket, 'odds', '') . '</div>';
//            $gameResult .= ($state == 1) ? '<div>' . '/ 開獎號碼: ' . array_get($ticket, 'lottery', '') . '</div>' : '';

            $now = date('Y-m-d H:i:s');

            $rawTicketArray = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 原生注單編號 (因為彩球沒有原生注單識別碼 所以改採uuid前七位數字為該原生編號)
                'bet_num' => substr(array_get($ticket, 'uuid'), '0', '7'),
                // 會員帳號識別碼
                'user_identify' => $sUserId,
                // 會員帳號
                'username' => $account,
                // 遊戲服務站(config/united_ticket.php 對應的索引值)
                'station' => $station,
                // 範疇，例如： 美棒、日棒
                'game_scope' => $gameScope ?? '',
                // 產品類別
                'category' => 'keno',
                // 押注類型，例如： 模式+場次+玩法
                // 如果第三方提供的資料不是單一欄位就能知道是什麼押注類型，就要把多個欄位資料串成一個
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => $cmount,
                // 有效投注 實際投注
                'valid_bet' => $cmount,
                // 有效投注 實際投注
                'rolling' => $cmount,
                // 輸贏結果(可正可負) 中獎金額 + 會員退水 - 下注金額
                'winnings' => $winnings,
                // 系統退水值
                'rebate_amount' => $retake,
                // 開牌結果
                'game_result' => $gameResult,
                // 作廢
                'invalid' => ($status == 1),
                // 投注時間
                'bet_at' => $batAt,
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


    public function rakeConverter(array $rawRakes = [], array $options = [])
    {
        $unitedRakes = [];

        foreach (array_get($rawRakes, 'rakes', []) as $rake) {

            // 如果是空值就跳過
            if (empty($rake)) {
                continue;
            }

            $account = array_get($rake, 'account');
            $betDate = array_get($rake, 'Bet_date');
            $gameScope = array_get($this->gameScope, array_get($rake, 'game_id'));

            $rawRakeModel = new SuperLotteryRake([
                'account' => $account,
                'bet_date' => $betDate,
                'game_scope' => $gameScope
            ]);

            // 回傳套用原生注單模組後的資料(會產生 uuid)
            $uuid = $rawRakeModel->uuid->__toString();
            $userId = array_get($options, 'user_identify');

            $rawRakeArray = [
                // uuid 識別碼
                'id' => $uuid,
                // 會員級別
                'level' => array_get($rake, 'level'),
                // 會員識別碼
                'user_identify' => $userId,
                // 帳號
                'account' => $account,
                // 投注日期
                'bet_date' => $betDate,
                // 遊戲範疇
                'game_scope' => $gameScope,
                // 產品類別
                'category' => 'keno',
                // 投注數量
                'ccount' => (int)array_get($rake, 'ccount'),
                // 投注金額
                'cmount' => (float)array_get($rake, 'cmount'),
                // 有效投注
                'bmount' => (float)array_get($rake, 'bmount'),
                // 會員中獎金額
                'm_gold' => (float)array_get($rake, 'm_gold'),
                // 會員退水
                'm_rake' => (float)array_get($rake, 'm_rake'),
                // 結果會員
                'm_result' => (float)array_get($rake, 'm_result'),
                // 結果代理
                'up_no1_result' => (float)array_get($rake, 'up_no1_rake'),
                // 結果總代理
                'up_no2_result' => (float)array_get($rake, 'up_no2_result'),
                // 退水代理
                'up_no1_rake' => (float)array_get($rake, 'up_no1_rake'),
                // 退水總代理
                'up_no2_rake' => (float)array_get($rake, 'up_no2_rake'),
                // 代理
                'up_no1' => array_get($rake, 'up_no1'),
                // 總代理
                'up_no2' => array_get($rake, 'up_no2'),
            ];

            // 查找各階水倍差佔成
            $rakesTableArray = $this->findRebate(
                $userId,
                'super_lottery',
                $gameScope,
                $betDate . ' 23:59:59'
            );

            $unitedRakes[] = array_merge($rawRakeArray, $rakesTableArray);
        }

        // 儲存整合注單
        SuperLotteryRake::replace($unitedRakes);

        return $unitedRakes;
    }
}