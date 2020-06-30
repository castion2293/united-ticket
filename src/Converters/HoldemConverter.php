<?php

namespace SuperPlatform\UnitedTicket\Converters;

/**
 * 「德州」原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
class HoldemConverter extends Converter
{
    /**
     * @var array
     */
    private $unitedTickets = [];

    /**
     * 轉換 (僅轉換，未寫入資料庫)
     *
     * @param array $aRawTickets
     * @param string $sUserId
     * @return array
     */
    public function transform(array $aRawTickets = [], string $sUserId = ''): array
    {
        //Poker
        foreach (array_get($aRawTickets, 'tickets', []) as $ticket) {

            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($ticket, 'PlayTime', null);
            $payoutAt = array_get($ticket, 'PlayTime', null);
            $userIdentify = array_get($ticket, 'MemberAccount');
            $now = date('Y-m-d H:i:s');

            $this->unitedTickets[] = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($ticket, 'uuid'),
                // 會員帳號識別碼
                'user_identify' => $userIdentify,
                // 遊戲服務站(config/ticket_integrator.php 對應的索引值)
                'station' => 'holdem',
                // 範疇，德州_撲克
                'game_scope' => 'holdem_poker',
                // 押注類型，通用
                'bet_type' => 'general',
                // 實際投注
                'raw_bet' => array_get($ticket, 'Bet'),
                // 有效投注 (處理中洞、退組、合局情況之後的投注額)
                // 有效投注的資料一開始會和實際投注一樣，當開獎結果出來之後，才會做修正
                'valid_bet' => array_get($ticket, 'Bet'),
                // 洗碼量(退水值)，用於佔成樹計算
                // 退水值 = 投注金額 * 2.5%
                'rolling' => array_get($ticket, 'ServicePoints'),
                // 輸贏結果(可正可負)
                'winnings' => array_get($ticket, 'WinLose'),
                // 投注時間
                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
                // 派彩時間
                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now
            ];
        }

        //Slot 彩金拉霸
        foreach (array_get($aRawTickets, 'slot_tickets', []) as $slotTicket) {

            // 整理「原生注單」對應「整合注單」的各欄位資料
            $betAt = array_get($slotTicket, 'Time', null);
            $payoutAt = array_get($slotTicket, 'Time', null);
            $userIdentify = array_get($slotTicket, 'MemberAccount');
            $now = date('Y-m-d H:i:s');

            $this->unitedTickets[] = [
                // 原生注單的 uuid 識別碼等同於整合注單的識別碼 id
                'id' => array_get($slotTicket, 'uuid'),
                // 會員帳號識別碼
                'user_identify' => $userIdentify,
                // 遊戲服務站(config/ticket_integrator.php 對應的索引值)
                'station' => 'holdem',
                // 範疇，德州_彩金拉霸
                'game_scope' => 'holdem_slot',
                // 押注類型，通用
                'bet_type' => 'general',
                // 拉霸紀錄沒有投注金額
                'raw_bet' => 0,
                // // 拉霸紀錄沒有投注金額
                'valid_bet' => 0,
                // 洗碼量
                // 拉霸紀錄不用跑佔成樹計算
                'rolling' => 0,
                // 輸贏結果(可正可負)
                'winnings' => array_get($slotTicket, 'ChangePoints'),
                // 投注時間
                'bet_at' => $betAt ? date('Y-m-d H:i:s', strtotime($betAt)) : null,
                // 派彩時間
                'payout_at' => $payoutAt ? date('Y-m-d H:i:s', strtotime($payoutAt)) : null,
                // 資料建立時間
                'created_at' => $now,
                // 資料最後更新
                'updated_at' => $now
            ];
        }

        return $this->unitedTickets;
    }
}