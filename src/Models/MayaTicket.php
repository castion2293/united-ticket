<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「碼雅」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class MayaTicket extends RawTicket
{
    protected $table = 'raw_tickets_maya';

    public $incrementing = false;

    protected $fillable = [
        // 遊戲名稱
        'GameType',
        // 用戶名
        'Username',
        // 遊戲平台會員主鍵ID
        'GameMemberID',
        // 注單編號
        'BetNo',
        // 下注金額
        'BetMoney',
        // 有效下注金額
        'ValidBetMoney',
        // 輸贏金額
        'WinLoseMoney',
        // 打賞
        'Handsel',
        // 下注內容
        'BetDetail',
        // 注單狀態
        'State',
        // 類型
        'BetType',
        // 下注時間
        'BetDateTime',
        // 結算時間
        'CountDateTime',
        // 帳務時間
        'AccountDateTime',
        // 注單賠率
        'Odds'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->Username,
            $this->BetNo
        ]);
    }
}