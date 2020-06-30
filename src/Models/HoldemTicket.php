<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「德州」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class HoldemTicket extends RawTicket
{
    protected $table = 'raw_tickets_holdem';

    protected $fillable = [
        'MemberAccount',
        'PlatformID',
        'RoundCode',
        'RoundId',
        'PlayTime',
        'OriginalPoints',
        'Bet',
        'WinLose',
        'LastPoints',
        'ServicePoints',
        'HandselServicePoints',
        'GSLogPath',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->MemberAccount,
            $this->RoundCode
        ]);
    }

}
