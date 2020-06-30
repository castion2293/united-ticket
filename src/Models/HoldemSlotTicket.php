<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「德州」彩金拉霸紀錄
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class HoldemSlotTicket extends RawTicket
{
    protected $table = 'raw_tickets_holdem';

    protected $fillable = [
        'PlatformID',
        'MemberAccount',
        'Time',
        'JPId',
        'BeforePoints',
        'ChangePoints',
        'AfterPoints',
        'JPName',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->MemberAccount,
            $this->JPId
        ]);
    }

}
