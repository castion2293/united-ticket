<?php

namespace SuperPlatform\UnitedTicket\Models;

class BoboPokerTicket extends RawTicket
{
    protected $table = 'raw_tickets_bobo_poker';

    protected $fillable = [
        'account',
        'betId',
        'gameNumber',
        'gameName',
        'result',
        'betDetailId',
        'betAmt',
        'earn',
        'content',
        'betTime',
        'payoutTime',
        'status'
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->betId,
            $this->betDetailId,
        ]);
    }
}