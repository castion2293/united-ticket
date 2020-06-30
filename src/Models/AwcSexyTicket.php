<?php

namespace SuperPlatform\UnitedTicket\Models;

class AwcSexyTicket extends RawTicket
{
    protected $table = 'raw_tickets_awc_sexy';

    protected $fillable = [
        'ID',
        'userId',
        'platformTxId',
        'platform',
        'gameCode',
        'gameType',
        'betType',
        'txTime',
        'betAmount',
        'winAmount',
        'turnover',
        'txStatus',
        'realBetAmount',
        'realWinAmount',
        'jackpotBetAmount',
        'jackpotWinAmount',
        'currency',
        'comm',
        'createTime',
        'updateTime',
        'bizDate',
        'modifyTime',
        'roundId',
        'gameInfo',
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->ID,
            $this->userId,
        ]);
    }
}