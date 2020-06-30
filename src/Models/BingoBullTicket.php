<?php

namespace SuperPlatform\UnitedTicket\Models;

class BingoBullTicket extends RawTicket
{
    protected $table = 'raw_tickets_bingo_bull';

    protected $fillable = [
        'status',
        'betNo',
        'betData',
        'realBetMoney',
        'openNo',
        'okMoney',
        'totalMoney',
        'pumpMoney',
        'reportTime',
        'createTime',
        'userType',
        'account',
        'roomCode',
        'coin',
        'mainGame',
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->betNo,
        ]);
    }
}