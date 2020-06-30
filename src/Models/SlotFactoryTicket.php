<?php

namespace SuperPlatform\UnitedTicket\Models;

class SlotFactoryTicket extends RawTicket
{
    protected $table = 'raw_tickets_slot_factory';

    protected $fillable = [
        'TransactionID',
        'AccountID',
        'RoundID',
        'GameName',
        'SpinDate',
        'Currency',
        'Lines',
        'LineBet',
        'TotalBet',
        'CashWon',
        'GambleGames',
        'FreeGames',
        'FreeGamePlayed',
        'FreeGameRemaining',
        'Type'
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->AccountID,
            $this->TransactionID,
        ]);
    }
}