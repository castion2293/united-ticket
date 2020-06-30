<?php

namespace SuperPlatform\UnitedTicket\Models;

class NineKLotteryTicket extends RawTicket
{
    protected $table = 'raw_tickets_nine_k_lottery';

    protected $fillable = [
        'BossID',
        'MemberAccount',
        'TypeCode',
        'GameDate',
        'GameTime',
        'GameNum',
        'GameResult',
        'WagerID',
        'WagerDate',
        'BetItem',
        'TotalAmount',
        'BetAmount',
        'PayOff',
        'Result'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->MemberAccount,
            $this->WagerID,
            $this->WagerDate
        ]);
    }
}